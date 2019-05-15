# diff_tester
A web application that will test the differences of a website.


## Please install first:
```
composer require "jonnyw/php-phantomjs:4.*"
sudo apt-get install php-imagick
```
Create a data base, and configure your application in the config.cnf file.

## Functionalities ##
#### Comparaison ####
Compare the screenshots and show the diferents between the selected date and today.

#### Refresh DataBase ####
Go on the website and search all the links that the user can find.
** Becarefull, drop the content of the database! **

#### Remove urls accents ####
Remove all the url accents in the database.

#### Url tester ####
Test all the urls of the database. Return all the 404 pages and the devlinks.

## Some dependies:
- [php phantomjs](http://jonnnnyw.github.io/php-phantomjs/)
- php-imagick

## Some screens :
![Screen of diff_tester](AppPictures/1.png?raw=true "list of difs")
![Screen of diff_tester](AppPictures/2.png?raw=true "show difs")
