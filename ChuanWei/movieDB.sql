create database ambassador default character set utf8;
use ambassador;

create table movies
(
  ID int not null auto_increment primary key,
  movie_name varchar(20),
  movie_enname varchar(30),
  movie_ID varchar(50),
  rating varchar(20),
  run_time varchar(10),
  info text,
  actor varchar(20),
  genre varchar(10),
  play_date varchar(20),
  poster text,
  trailer text
);

create table comingMovies
(
  ID int not null auto_increment primary key,
  movie_name varchar(20),
  movie_enname varchar(30),
  movie_ID varchar(50),
  info text,
  actor varchar(20),
  genre varchar(10),
  play_date varchar(20),
  poster text,
  trailer text
);

create table theaters
(
 ID int not null auto_increment primary key,
 theater_name varchar(20),
 address varchar(30),
 phone varchar(20),
 img text
);

create table movietime
(
  id int not null auto_increment primary key,
  movie_ID varchar(50),
  theater_name varchar(20),
  seat_tag varchar(20),
  time varchar(10),
  seat_info varchar(10)
);

create table movieday
(
  ID int not null auto_increment primary key,
  movie_ID varchar(50),
  weekday varchar(20),
  date date
);
