# words-of-our-culture

> Visit my [Blog](http://www.rai.org.uk) to get in touch or to
see demos of this and much more.

## Overview

This project is a linguistic analysis of the utterances within all the great english language movies, from the birth of sound cinema to the present day. It gathers together a corpus of all the speech from those movies and uses various statistical techniques to examine the words.

The main output is a visual model of [the words which most characterise a given movie](http://rai.org.uk/wooc/random.php?type=movie) and, correspondingly, [the movies which most align with a given word](http://rai.org.uk/wooc/random.php?type=word). Aside from this, there is further linguistic analysis in this document too.

## The Movies

So which movies have I used to form this corpus? Well the criteria for inclusion was very simple, I chose movies that were:

* Popular and likely to be well known
* Made after the invention of sound on film (obviously)
* Contain speech mostly in the English language
* Balanced within the years being considered (1930 - 2017)
* I've seen them and I like them :smiley:

You can click [here](http://rai.org.uk/wooc/content.php) to see a full list of all the movies that form the corpus.

## The Corpus

In linguistics, a _corpus_ is defined as "a collection of written or spoken material stored on a computer and used to find out how language is used". The main route to building the corpus of movie utterances was via the presence of subtitles. These are readily available for most headline movies and are primary for use as closed captions within online and DVD versions.

### Phase One: Gathering the Corpus

Collecting and utilising subtitles proved to be non-trivial exercise. A great many of the openly available subtitle tracks were of low quality and contained many errors and even omissions. Consequently, there was a lot of data "wrangling" that I had to deploy in order to extract useful and accurate text from the subtitle tracks. You can see the details of that wrangling by looking at the implementation within the [corpus section](https://github.com/pete-rai/words-of-our-culture/tree/master/corpus) of the codebase.

Whilst forming the corpus I wanted to make a list of _all the things that were said_, but did not concern myself with _who said it_. Hence after the first phase of corpus formation I arrive at blocks of texts that contains all the utterances and corresponding punctuation, but none of the names of the speakers.

Here is an example of the first phase corpus text from the movie Casablanca:

> They grab Ugarte, then she walks in. That's the way it goes. One in, one out. Sam. Yes, boss? If it's December 1941 in Casablanca, what time is it in New York? What? My watch stopped. I bet they're asleep in New York. I bet they're asleep all over America. Of all the gin joints in all the towns in all the world she walks into mine. What's that you're playing? A little something of my own. Well, stop it. You know what I want to hear. No, I don't. You played it for her. You can play it for me. I don't think I can remember. If she can stand it, I can. Play it. Yes, boss.

Occasionally, there were sections of non-spoken text with the subtitle tracks. These are audio descriptions that describes significant sounds for death audiences. For example, a track may have contained a sentence "the car is heard screeching it tyres as it speeds away". In the majority of cases, these sections were well marked and easy to eliminate. Some may have crept into the corpus but not enough to significantly alter the context of the corpus.

### Phase Two: Normalising the Words


### Phase Three: Stemming the Words







_– [Pete Rai](http://www.rai.org.uk)_
