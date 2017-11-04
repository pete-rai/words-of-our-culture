# words-of-our-culture

> Visit my [Blog](http://www.rai.org.uk) to get in touch or to
see demos of this and much more.

## Overview

This project is a linguistic analysis of the utterances within all the great english language movies, from the birth of sound cinema to the present day. It gathers together a corpus of all the speech from those movies and uses various statistical techniques to examine the words.

The main output is a visual model of [the words which most characterise a given movie](http://rai.org.uk/wooc/random.php?type=movie) and, correspondingly, [the movies which most align with a given word](http://rai.org.uk/wooc/random.php?type=word). Aside from this, there is further linguistic analysis in this document too.

## The Movies

So how did I go out choosing movies for inclusion within the corpus? Well the criteria was very simple, I chose movies which were:

* Popular and likely to be well known
* Made after the invention of sound on film (for obvious reasons)
* Contain speech which is mostly in the English language
* Balanced within the years being considered (1930 - 2017)
* I've seen them and I like them :smiley:

You can click [here](http://rai.org.uk/wooc/content.php) to see a full list of all the movies that form the corpus.

## The Corpus

In linguistics, a _corpus_ is defined as "a collection of written or spoken material stored on a computer and used to find out how language is used". The main route to building the corpus of movie utterances was via the presence of subtitles. These are readily available for most headline movies and are primary for use as closed captions within online and DVD versions.

### Phase One: Gathering the Corpus

Collecting and utilising subtitles proved to be non-trivial exercise. A great many of the openly available subtitle tracks were of low quality and contained many errors and even omissions. Consequently, there was a lot of data "wrangling" that I had to deploy in order to extract useful and accurate text from the subtitle tracks. You can see the details of that wrangling by looking at the implementation within the [corpus section](https://github.com/pete-rai/words-of-our-culture/tree/master/corpus) of the codebase.

Whilst forming the corpus I wanted to make a list of _all the things that were said_, but did not concern myself with _who said it_ or the _order in which things were said_. Hence after the first phase of corpus formation I arrive at blocks of texts that contains all the utterances and corresponding punctuation, but none of the names of the speakers.

Here is an example of the first phase corpus text from the movie [Casablanca](https://en.wikipedia.org/wiki/Casablanca_(film)):

> They grab Ugarte, then she walks in. That's the way it goes. One in, one out. Sam. Yes, boss? If it's December 1941 in Casablanca, what time is it in New York? What? My watch stopped. I bet they're asleep in New York. I bet they're asleep all over America. Of all the gin joints in all the towns in all the world she walks into mine. What's that you're playing? A little something of my own. Well, stop it. You know what I want to hear. No, I don't. You played it for her. You can play it for me. I don't think I can remember. If she can stand it, I can. Play it. Yes, boss.

Occasionally, there were sections of non-spoken text with the subtitle tracks. These are audio descriptions that describes significant sounds for death audiences. For example, a track may have contained a sentence "the car is heard screeching it tyres as it speeds away". In the majority of cases, these sections were well marked and easy to eliminate. Some may have crept into the corpus but not enough to significantly alter the context of the corpus.

### Phase Two: Normalising the Words

Once the corpus had been gathered, the next phase was to normalise the text in order to facilitate matching across the movies. The normalisation method used was quite simple (note the order of the steps below is significant).

1. Conversion of accented characters to their "normal" English language variant. So, for example, the accented letter "é" was normalised to "e" and the letter "ä" was normalised to "a", and so on.
2. All whitespace was normalised to a single space. This step removed non-space white space like tabs, new-lines, etc.
3. All punctuation was removed. So, for example, the word "it's" became "its" and the word "you're" was normalised to "youre", and so on. Note this also removes full stops, so at this point we also lose sentence breaks.
4. All text we converted to lowercase.

Here is an example of the same text from Casablanca shown in the earlier section, but after the normalisation process has been executed upon it:

> they grab ugarte then she walks in thats the way it goes one in one out sam yes boss if its december 1941 in casablanca what time is it in new york what my watch stopped i bet theyre asleep in new york i bet theyre asleep all over america of all the gin joints in all the towns in all the world she walks into mine whats that youre playing a little something of my own well stop it you know what i want to hear no i dont you played it for her you can play it for me i dont think i can remember if she can stand it i can play it yes boss

Note that, if you plan to use the source code for your own use, it's important to maintain a multi-byte text encoding chain on all your processes. If you don't, then some of the more exotic characters will be lost before step 1 above and properly handle them.

### Phase Three: Stemming the Words

The final text preparation phase was to apply a word stemmer algorithm. A _stemmer_ is defined as "an algorithm for removing inflectional and derivational endings in order to reduce word forms to a common stem". That sounds quite complicated, but the concept is actually very simple.

When we count word occurrences, we don't want to make a distinct between the many variants of the same root word. So, for example, we want to treat all "happy", "happily", "happier", "happiest", etc all as the same single word. Clearly the _root word_ for these is "happy", but identifying the root word for all words in English is a next to impossible task. Instead we use an algorithm to reduce all these words to the same _stem_.

The stem for all these words is the single word "happi". But wait, "happi" is not an valid English language word. It is important to understand that stemming is used to find a stem, not the root word. There is no reason for the stem to be a valid word in itself; rather only that all the right words reduce to the same stem.

For this project, I used a very popular English language stemming algorithm called Porter Stemmer. The Porter Stemmer was created by the English linguist [Mark Porter](https://en.wikipedia.org/wiki/Martin_Porter) and the [PHP implementation I used](https://github.com/pete-rai/words-of-our-culture/blob/master/lib/stemmer.php) was developed by [Richard Heyes](http://www.phpguru.org).

Here is an example of the same text from Casablanca shown in the earlier section, but after the stemming process has been executed upon it:

> thei grab ugart then she walk in that the wai it goe on in on out sam ye boss if it decemb 1941 in casablanca what time is it in new york what my watch stop i bet theyr asleep in new york i bet theyr asleep all over america of all the gin joint in all the town in all the world she walk into mine what that your plai a littl someth of my own well stop it you know what i want to hear no i dont you plai it for her you can plai it for me i dont think i can rememb if she can stand it i can plai it ye boss

Whilst we use stemming to provide consistent handling of commonly rooted words, we do still maintain a careful map from the stem to the actual words uttered in the movies.

## The Data Model

The data model used for the project is simple and concise. There only three entities types:

Entity | Description
--- | ---
Content | A _movie_
Utterance | A _word_
Occurrence | An instance of a _word_ said within a _movie_

The model was implemented within a relational database; more specifically I used MySQL. The data counts are well within the MySQL capabilities and hence, I did not require use of any big-data tech. The tables live within a classic many-to-many formation:

```

        ┌──────────────┐            ┌──────────────┐
        │   CONTENT    │            │  OCCURRENCE  │            ┌──────────────┐
        ├──────────────┤            ├──────────────┤            │  UTTERANCE   │
        │ id           ├─ 1 ──── * ─┤ content_id   │            ├──────────────┤
        │ title        │            │ utterance_id ├─ * ──── 1 ─┤ id           │
        │ country      │            │ tally        │            │ stem         │
        │ year         │            └──────────────┘            │ utterance    │
        └──────────────┘                                        └──────────────┘

```

Note: In the model you will find reference to fields called "pos". This stands for part-of-speech. In this implementation, the only pos value I use is "word". Another valid, and often enlightening, value is "bi-gram", which is a pair of words (for example, "New York"). Analysis based on bi-grams can be very useful, but it significantly swells the data counts. After bi-grams, comes tri-grams or more generally n-grams.

Note: In the model you will find a number of additional denormal tables. The use of denormal tables is generally very bad practice within an RDBMS. Normally I would shun such database abuses. However, two things lead to the decision to use denormal tables. Firstly, it means that data access for the visualisation becomes trivial, allowing me to host on a low-grade (read free) hosting server. Secondly, it's a one-time, write-only schema, so keeping the denormal tables up to date is not a worry. This latter consideration is also the reason why I commented out all the constraints in the [schema definition](https://github.com/pete-rai/words-of-our-culture/blob/master/db/schema.ddl).

## Log-Likelihood

In order to do the analysis for the main [bubbles visualisation](http://rai.org.uk/wooc/random.php?type=movie)) I use a statistical technique called log-likelihood. This helps us to identify the _significant_ words in a given movie.

It is important to understand that the significant words are not the same as the _frequently used_ words. The most frequently used words in movie are generally also the most frequently used words in general English language discourse. They reveal nothing significant about the context of the movie in which they are uttered. For example, here are the ten most frequently uttered words across the whole corpus:

1. you
2. the
3. i
4. to
5. a
6. and
7. it  
8. of  
9. that
10. in

As you see, nothing of any great interest there. Let's try narrowing down to just one specific movie and again we pick Casablanca:

1. you
2. i
3. the
4. to
5. a
6. it
7. in
8. of
9. that
10. is

Again, nothing of note. In fact, almost the same as the frequency counts in the wider corpus. However, if we list onwards we find that that 24th most frequently uttered word in Casablanca is "rick". This is, of course, the name of [Humphrey Bogart](https://en.wikipedia.org/wiki/Humphrey_Bogart)'s iconic character in that great movie. Whilst the word "rick" is frequently said in Casablanca, it's not a word we would expect to be used much on a day-to-day basis. In fact, it is only mentioned in 2.3% of the movies of the corpus. Whilst "rick" is said 74 times in Casablanca, it is only said 0.2 times on average in all movies. Hence "rick" is a _statistically significant_ word in the movie Casablanca.




_– [Pete Rai](http://www.rai.org.uk)_
