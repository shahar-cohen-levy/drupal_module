# Spotify artists

## Table of contents

- Introduction
- Requirements
- Installation
- Configuration

## Introduction

Spotify Artists module connects to the Spotify API.
An administrator is able to save up to 20 Spotify artist ids.
A list of these artists’ names will be displayed in a block.
When logged in, each name will link to a page on the website
showing more information about that artist.
This artist page should only be visible to logged-in users.

## Requirements

This module requires no modules outside of Drupal core.

## Installation

1. Extract 'spotify_artists' folder into Drupal's custom
module folder (modules/custom).
2. Enable the module. You may use this drush command:
`drush en spotify_artists`

## Configuration

Configure API settings:
1. Get client ID & Secret from [Spotify](https://developer.spotify.com/documentation/web-api/tutorials/getting-started#create-an-app).
2. Go to `/admin/config/content/spotify_api`.
3. Enter client ID & Secret and submit.

If 'Olivero' is the default theme, the module works out-of-the-box
using default artists in the header.

For artists' configuration go to `/admin/config/content/spotify_artists`.

In order to add artists:
1. Enter an artist name. e.g., Dua Lipa.
2. Choose the artist from the results and click on 'Add selection to list'.

In order to delete artists:
1. Select artist/s from the table.
2. click on 'Delete selected items'

To place the Artists block follow [these](https://www.drupal.org/docs/user_guide/en/block-place.html) instructions.