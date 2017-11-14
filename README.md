# words-of-our-culture

> Visit my [Blog](http://www.rai.org.uk) to get in touch or to
see demos of this and much more.

## Overview

This project is a linguistic analysis of the utterances within all the great English language movies, from the birth of sound cinema to the present day. It gathers together a corpus of all the speech from those movies and uses various statistical techniques to examine the words that were uttered.

> This readme covers mostly _how the project works_ rather than _what the project told us_.

The main output of this project is a visual model of [the words which most characterise a given movie](http://rai.org.uk/wooc/random.php?type=movie) and, correspondingly, [the movies which most align with a given word](http://rai.org.uk/wooc/random.php?type=word). This visualisation uses two earlier projects that I created, which are both available here on [my GitHub](https://github.com/pete-rai). One is a [bubbles visualisation](https://github.com/pete-rai/p5js-bubbles) and the other is a [jQuery slide-in control](https://github.com/pete-rai/jquery-slidein). Here are a few more examples of the visualisation in action:

By Movie | By Word
:---: | :---:
[Psycho](http://rai.org.uk/wooc/bubbles.php?topic=tt0054215) | [France](http://rai.org.uk/wooc/bubbles.php?topic=france)
[The Italian Job](http://rai.org.uk/wooc/bubbles.php?topic=tt0064505) | [Jesus](http://rai.org.uk/wooc/bubbles.php?topic=jesus)
[Casablanca](http://rai.org.uk/wooc/bubbles.php?topic=tt0034583) | [Nuclear](http://rai.org.uk/wooc/bubbles.php?topic=nuclear)
[JFK](http://rai.org.uk/wooc/bubbles.php?topic=tt0102138) | [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;President&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;](http://rai.org.uk/wooc/bubbles.php?topic=president)
[Back to the Future](http://rai.org.uk/wooc/bubbles.php?topic=tt0088763) | [Gold](http://rai.org.uk/wooc/bubbles.php?topic=gold)

> I have also got the whole thing working with the top one million pages on Wikipedia. It was a big job in terms of data sizes, but much easier in terms of corpus gathering and cleaning. However, I cannot cram that implementation (which still fits into a MySQL by the way) onto my rather limited hosting environment. Do get in touch via my [blog](http://rai.org.uk) if you want access to the Wikipedia version.

## The Movies

So how did I go out choosing movies for inclusion within the corpus? Well the criteria was very simple, I chose movies which were:

* Popular and likely to be well known
* Made after the invention of sound on film (for obvious reasons)
* Contain speech which is mostly in the English language
* Balanced within the years being considered (1930 - 2017)
* I've seen them and I like them :smiley:

You can click [here](http://rai.org.uk/wooc/content.php) to see a full list of all the movies that form the corpus.

Here is a list of the most wordy movies in the corpus:

 pos | movie | words / min
 :---: | --- | :---:
   1 | [The Internship](http://rai.org.uk/wooc/bubbles.php?topic=tt2234155)      | 169.7815
   2 | [Death of a Salesman](http://rai.org.uk/wooc/bubbles.php?topic=tt0043458) | 165.9130
   3 | [Stage Door](http://rai.org.uk/wooc/bubbles.php?topic=tt0029604)          | 164.4891
   4 | [His Girl Friday](http://rai.org.uk/wooc/bubbles.php?topic=tt0032599)     | 162.7065
   5 | [Horrible Bosses](http://rai.org.uk/wooc/bubbles.php?topic=tt1499658)     | 156.8878

If those movies made your ears sore, here are the least wordy movies:

 pos | movie | words / min
 :---: | --- | :---:
   5 | [Fantasia](http://rai.org.uk/wooc/bubbles.php?topic=tt0032455)               | 14.3040
   4 | [Eraserhead](http://rai.org.uk/wooc/bubbles.php?topic=tt0074486)             |  8.2235
   3 | [City Lights](http://rai.org.uk/wooc/bubbles.php?topic=tt0021749)            |  6.5287
   2 | [Modern Times](http://rai.org.uk/wooc/bubbles.php?topic=tt0027977)           |  3.4368
   1 | [One Million Years B.C.](http://rai.org.uk/wooc/bubbles.php?topic=tt0060782) |  1.7100

## The Corpus

In linguistics, a _corpus_ is defined as "a collection of written or spoken material stored on a computer and used to find out how language is used". The main route to building the corpus of movie utterances was via the presence of subtitles. These are readily available for most headline movies and are primary for use as closed captions within online and DVD versions.

> You will NOT find the actual corpus checked-in into GitHub for copyright reasons. The text within the corpus "belongs" to the rights holders of the movies. If you want to get access to the actual raw corpus, then please contact me via the email address listed on my [blog](http://rai.org.uk).

### Phase One: Gathering

Collecting and utilising subtitles proved to be non-trivial exercise. A great many of the openly available subtitle tracks were of low quality and contained many errors and even omissions. Consequently, there was a lot of data "wrangling" that I had to deploy in order to extract useful and accurate text from the subtitle tracks. You can see the details of that wrangling by looking at the implementation within the [corpus section](https://github.com/pete-rai/words-of-our-culture/tree/master/corpus) of the codebase.

Whilst forming the corpus, I wanted to make a list of _all the things that were said_, but did not concern myself with _who said it_ or _the order in which things were said_. Hence, after the first phase of corpus formation, I arrived at blocks of text that contained all the utterances and corresponding punctuation, but none of the names of the speakers.

Here is an example of the first phase corpus text from the movie [Casablanca](https://en.wikipedia.org/wiki/Casablanca_(film)):

> _They grab Ugarte, then she walks in. That's the way it goes. One in, one out. Sam. Yes, boss? If it's December 1941 in Casablanca, what time is it in New York? What? My watch stopped. I bet they're asleep in New York. I bet they're asleep all over America. Of all the gin joints in all the towns in all the world she walks into mine. What's that you're playing? A little something of my own. Well, stop it. You know what I want to hear. No, I don't. You played it for her. You can play it for me. I don't think I can remember. If she can stand it, I can. Play it. Yes, boss._

Occasionally, there were sections of non-spoken text within the subtitle tracks. These were audio descriptions that outline significant sounds for death audiences. For example, a track may have contained a sentence "the car is heard screeching it tyres as it speeds away". In the majority of cases, these sections were well marked and easy to eliminate. Some may have crept into the corpus, but not enough to significantly alter the context.

### Phase Two: Normalising

Once the corpus had been gathered, the next phase was to normalise the text in order to facilitate matching across the movies. The normalisation method used was quite simple (note the order of the steps below is significant).

1. Conversion of accented characters to their "normal" English language variant. So, for example, the accented letter "é" was normalised to "e" and the letter "ä" was normalised to "a", and so on.
2. All whitespace was normalised to a single space. This step also removed all non-space whitespace like tabs, new-lines, etc.
3. All punctuation was removed. So, for example, the word "it's" became "its" and the word "you're" was normalised to "youre", and so on. Note this also removed full stops, so at this point I also lost sentence breaks.
4. All text was converted to lowercase.

Here is an example of the same text from Casablanca shown in the earlier section, but after the normalisation process has been executed upon it:

> _they grab ugarte then she walks in thats the way it goes one in one out sam yes boss if its december 1941 in casablanca what time is it in new york what my watch stopped i bet theyre asleep in new york i bet theyre asleep all over america of all the gin joints in all the towns in all the world she walks into mine whats that youre playing a little something of my own well stop it you know what i want to hear no i dont you played it for her you can play it for me i dont think i can remember if she can stand it i can play it yes boss_

Note that, if you plan to use the source code for your own use, it's important to maintain a _multi-byte text encoding chain_ on all your processes. If you don't, then some of the more exotic characters will be lost before step two above can properly handle them.

### Phase Three: Stemming

The final text preparation phase was to apply a word stemmer algorithm. A _stemmer_ is defined as "an algorithm for removing inflectional and derivational endings, in order to reduce word forms to a common stem". That sounds quite complicated, but the concept is actually very simple.

When we count word occurrences, we don't want to make a distinction between the many variants of the same root word. So, for example, we want to treat "happy", "happily", "happier", "happiest", etc all as the same single word. Clearly the _root word_ for these is "happy", but identifying the root word for all words in the English language is a next to impossible task. Instead we use an algorithm to reduce all these words to the same _stem_.

The stem for all these happy words is the single word "happi". But wait, "happi" is not a valid English language word! It is important to understand that stemming is used to find a stem word, not the root word. There is no reason for the stem to be a valid word in itself; rather only that all the right words consistently reduce to the same stem.

For this project, I used a very popular English language stemming algorithm called Porter Stemmer. The Porter Stemmer was created by the English linguist [Mark Porter](https://en.wikipedia.org/wiki/Martin_Porter) and the [PHP implementation I used](https://github.com/pete-rai/words-of-our-culture/blob/master/lib/stemmer.php) was developed by [Richard Heyes](http://www.phpguru.org).

Here is an example of the same text from Casablanca shown in the earlier section, but after the stemming process has been executed upon it:

> _thei grab ugart then she walk in that the wai it goe on in on out sam ye boss if it decemb 1941 in casablanca what time is it in new york what my watch stop i bet theyr asleep in new york i bet theyr asleep all over america of all the gin joint in all the town in all the world she walk into mine what that your plai a littl someth of my own well stop it you know what i want to hear no i dont you plai it for her you can plai it for me i dont think i can rememb if she can stand it i can plai it ye boss_

Whilst I used stemming to provide consistent handling of commonly rooted words, I did still maintain a careful map from the stem to the actual words uttered in the movies. This is so that I can show meaningful English language results to users.

## The Data Model

The data model used for the project is simple and concise. There only three entities types:

Entity | Description
--- | ---
Content | A _movie_
Utterance | A _word_
Occurrence | An instance of a _word_ said within a _movie_

The model was [implemented within](https://github.com/pete-rai/words-of-our-culture/blob/master/db/schema.ddl) a relational database; more specifically I used MySQL. The data counts were well within MySQL capabilities and hence, I did not require use of any big-data tech. The tables live within a classic many-to-many formation:

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

Note: In the model, you will find reference to fields called "pos" which stands for part-of-speech. In this implementation, the only pos value I used was "word". Another valid value is "bi-gram", which is a pair of words (for example "New York" or "Blue Parrot"). Analysis based on bi-grams can be very useful, but it significantly swells the data counts. After bi-grams, comes tri-grams and then more generally [n-grams](https://en.wikipedia.org/wiki/N-gram) (for example "letters of transit" or "heres looking at you").

Note: In the implementation, you will find the deployment a number of additional denormal tables. The use of denormal tables is generally considered very bad practice within an RDBMS; normally I would shun such database abuse. However, two things lead to my decision to use denormal tables. Firstly, it meant that data access for the visualisation became trivial, allowing me to host on a low-grade hosting server. Secondly, the database is a one-time-write schema, so keeping the denormal tables up to date is not an issue. These two considerations are also the reason why I commented out all the constraints within the [schema definition](https://github.com/pete-rai/words-of-our-culture/blob/master/db/schema.ddl).

## The Analysis

In order to do the analysis for the main [bubbles visualisation](http://rai.org.uk/wooc/random.php?type=movie), I used a statistical technique called _log-likelihood_. This helped me to identify the _significant_ words used within a given movie.

### Significant Words

It is important to understand, that the _significant_ words are not the same as _frequently used_ words. The most frequently used words in movies are also the most frequently used words in normal English language discourse. They reveal nothing significant about the context of the movie in which they are uttered. For example, here are (in order) the eight most frequently uttered words across the whole corpus:

> you, the, i, to, a, and, it, of  

As you can see, there is nothing of any great interest in these. Let's try narrowing down to just one specific movie and again we pick Casablanca:

> you, i, the, to, a, it, in, of

Again, there is nothing of note. In fact, it's almost the same as the frequency counts in the wider corpus.

However, if we list onwards, we find that that 24<sup>th</sup> most frequently uttered word in Casablanca is "rick". This is, of course, the name of [Humphrey Bogart](https://en.wikipedia.org/wiki/Humphrey_Bogart)'s iconic character in that great movie. Whilst the word "rick" is said reasonably frequently in Casablanca, it's not a word that we would expect to be used much on a day-to-day basis. In fact, it is only uttered in 2.3% of the movies in the whole corpus. Whilst "rick" is said 74 times in Casablanca, it is only said 0.2 times on average across all the movies. Hence, "rick" is a _statistically significant_ word in the movie Casablanca. It occurs more often that one would expect from normal discourse. Other such words in Casablanca are "visa", "letters", "lisbon", "ilsa", etc.

### Log-Likelihood

Log-likelihood is a statistical technique that helps us identify significant words in a given movie when compared with the wider corpus. Essentially, it helps us compare how likely a word would be to occur in _any_ movie and then contrast that with how often it actually occurs in a _specific_ given movie. This likelihood value is akin to looking for the _probability of occurrence_ for a given word.

You can find a very detailed explanation of log-likelihood over on it's [Wikipedia page](https://en.wikipedia.org/wiki/Likelihood_function#Log-likelihood). But, as with a lot of things on Wikipedia, you may find the description there too technical and hence hard to follow.

> ![log-likelihood equation](https://wikimedia.org/api/rest_v1/media/math/render/svg/46ddce58450790cb970a5acfa1b18faa83b0a4d4)

Luckily for us, a much more understandable explanation can be found over at the computer department of [Lancaster University](http://ucrel.lancs.ac.uk/llwizard.html). Here [Dr Paul Rayson](http://www.lancaster.ac.uk/staff/rayson/) provides a much more accessible description of the technique and how to apply it. In fact, his article is the basis of [the implementation](https://github.com/pete-rai/words-of-our-culture/blob/master/ui/lib/loglikelihood.php) that I used within this project.



_– [Pete Rai](http://www.rai.org.uk)_
