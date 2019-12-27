# Mondrian theme for Pico CMS 

[Pico CMS](https://github.com/picocms/Pico) is a stupidly simple, blazing fast, flat file CMS.

It clean blog theme with references to Piet Mondrian ([wiki](https://en.wikipedia.org/wiki/Piet_Mondrian)) in style elements. 

## How it looks like
+ https://imgur.com/Wmp7U1e - screenshot
+ https://imgur.com/4yOqYeb - mobile screenshot
+ https://imgur.com/5FK72FW - mobile screenshot with tags

## How to install
1. You can use [shell script](https://github.com/codeandmedia/pico_deploy) to deploy virtual machine with the theme and Pico CMS on Linode, DO, Vultr and other providers.
2. Or see [Pico CMS docs](http://picocms.org/docs/#themes)
**3. After installation do not forget to change settings in config.yml** site_title in the head, and custom settings in the end of config.yml

## Features
+ Simple design. You can store your content in separate folders (see an example in the repo above), but all posts will be available by date
+ Tags
+ Five articles per page with pagination
+ Search
+ 100% valid for [schema.org](https://schema.org)
+ Responsive design
+ Table of content (if you have more then 4 H2 elements)

## Article structure

```
---
Title: Four article
Description: Description of four article
Tags: tag,article,four
Template: post
Date: 2019-04-27
Cover: orange
Image: cat.jpg
---

```

+ *Cover* is color or image or even color and image *an example blue url(../assets/cat.jpg)* for the cover of post in index page. You can use HEX as you want.
+ *Image* is your article image. It's optional

## Many thanks to
[Brice Boucard](https://github.com/bricebou/pico_momh) for pagination and some ideas
[Pontus Horn](https://github.com/PontusHorn) for search and updates
and all contributors and plugin authors of Pico CMS

## Notes
+ If you create folder, don't forget to create index.md with head: 
```
---
Title: Folder name
Template: index
Level: top
---
```
+ All images for cover\image should be stored in assets
+ Plugins: [PicoSearch](https://github.com/PontusHorn/Pico-Search); [mcb_TableOfContent](https://github.com/mcbSolutions/Pico-Plugins/tree/master/mcb_TableOfContent); [PicoTags](https://github.com/bricebou/PicoTags);

