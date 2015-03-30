# PHP Voicemail with Tropo

Copyright (c) 2015 Adam Kalsey. Released under MIT license. See LICENSE file for
details.

## Introduction

PHP Voicemail is a simple voicemail script designed for people who would like a
second phone number that functions as a voicemail box. Use cases envisioned are
a small business that has no fixed line but uses mobile phones, throwaway numbers
for Craigslist ads, or community organizations that want a voice number backed by
nothing but a mailbox.

Messages transcribed and emailed as a WAV file with transcription to you using
your mail server. [Tropo](http://tropo.com) is used to provide the phone and
transcription services.

## Setting up

Dependancies are installed using Composer. From the command line, change to the
php-voicemail directory and run `composer install` to install dependancies.

PHP Voicemail uses Slim Framework to expose it's urls. Your web server should be
set up to allow for rewriting of URLs in order for Slim to properly expose routes.
See [Slim's rewrite documentation](http://docs.slimframework.com/#Route-URL-Rewriting)
for details on what this means and how to do it.

Audio and transcription files will be stored in the `audio` directory. This
directory must be writable by the web server.

Rename `sample.config.json` to `config.json`. Edit this file to add your mail server
configuration. A full list of config keys and their meanings can be found later
in this document.

On your server, visit `http://example.com/path/test` where `example.com` is your
host, and `path` is the directory path in which you installed PHP Voicemail. You
will see output like the following.

    Your Tropo Script URL is `http://example.com/path/tropo.php`
    Your files will be stored in `/var/www/example/public_html/path/audio`

Copy that Tropo Script URL. You'll need it in the next step.

Create a Tropo account at http://tropo.com and create a new Tropo
Application. Choose Scripting API for the type. In the form field, instead of
creating a "New Script" or "Select My Files", enter the Tropo Script URL you copied
earlier.

Choose a phone number and click "Create App".

Wait a moment or two for your number to be provisioned and give it a call. Leave
a message and you'll get a voicemail delivered to your email box.

## Config Glossary

* *mailserver*: (required) The email server's hostname. Probably something like
smtp.example.com

* *mailport*: (required) SMTP Port number for the outgoing mail server

* *mailuser*: If your mail server requires authentication, the username

* *mailpassword*: If your mail server requires authentication, the password

*	*mailtls*: Use TLS connections?

* *mailfrom*: (required) The address voicemail messages should be emailed from.

* *mailto*: (required) The address voicemail messages should be emailed to.

* *greeting*: The voicemail greeting. If text, will be read out. If a WAV or MP3
URL, will be played.

* *transfer*: If set, calls to your number will be forwarded to this number. Will
ring for 15 seconds before voicemail picks up. Format with the country code as
+12125551212

If `transfer` is set, Tropo requires that your account be enabled for outbound
calling access. Email support@tropo.com to request account verification. The
numbers you can dial are also restricted. See
 https://www.tropo.com/docs/scripting/international-features/international-dialing-sms
