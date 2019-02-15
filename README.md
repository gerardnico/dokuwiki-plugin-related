# Dokuwiki Related Plugin 

[![Build Status](https://travis-ci.org/gerardnico/dokuwiki-plugin-related.svg?branch=master)](https://travis-ci.org/gerardnico/dokuwiki-plugin-related)


## About

This plugin shows a list of related page for the actual page based on:

  * its backlinks  
  * and the reputation of each backlinks. ie the order is by importance which means that the link at the top of the related links would point
to the page that has the most backlinks.

## Usage

Add the related tag in your page

```txt
<related>
```
## Configuration

Important:

  * The maximum of related links to show
  * A optional [pattern](https://www.dokuwiki.org/devel:syntax_plugins#patterns) used when migrating for instance from the [Backlinks](https://www.dokuwiki.org/plugin:backlinks) 

Tip: If you want more customization, the [backlinks plugin](https://www.dokuwiki.org/plugin:backlinks) may have what you want.







