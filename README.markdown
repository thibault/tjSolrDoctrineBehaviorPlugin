# tjSolrDoctrineBehaviorPlugin #

The `tjSolrDoctrineBehaviorPlugin` provides a Doctrine behavior to easily index and search model objects
in the Solr search engine.

## Installation ##

### Plugin installation ###

You can install this plugin the usual way (RTFM), or if you want to work with the trunk:

    $ cd plugins
    $ git clone git://github.com/thibault/tjSolrDoctrineBehaviorPlugin.git

Then activate the plugin in the `config/ProjectConfiguration.class.php` file.


### Setting up Solr ###

The recommended Solr version is the latest (1.4). It is not tested with any other Solr version.

A Solr installation is already embedded in the plugin, in the `lib/vendor/solr` directory.
It's a default installation, and the only modified file is the `schema.xml`.

If you want to use your own Solr installation, add those lines to the schema, in the "fields" section:

    [xml]
    <!-- unique document id -->
    <field name="sf_unique_id" type="string" indexed="true" stored="true" required="true" />

    <!-- indexed object class -->
    <field name="sf_meta_class" type="string" indexed="true" stored="true" required="true" />

    <!-- indexed object id -->
    <field name="sf_meta_id" type="sint" indexed="true" stored="true" required="true" />

    <!-- default search field -->
    <field name="sf_text" type="text" indexed="true" stored="true" multiValued="true" />

And after the fields definition:

    [xml]
    <!-- Field to use to determine and enforce document uniqueness.
    Unless this field is marked with required="false", it will be a required field
    -->
    <uniqueKey>sf_unique_id</uniqueKey>

    <!-- field for the QueryParser to use when an explicit fieldname is absent -->
    <defaultSearchField>sf_text</defaultSearchField>

    <!-- copyField commands copy one field to another at the time a document
    is added to the index.  It's used either to index the same field differently,
    or to add multiple fields to the same field for easier/faster searching.
    -->
    <copyField source="*_t" dest="sf_text" />
    <copyField source="*_s" dest="sf_text" /

Before starting Solr, you need to create the log directory.

    $ mkdir log/solr

Once your configuration is correct, you can start Solr. Of course, you need a java installation.

    $ cd plugins/tjSolrDoctrineBehaviorPlugin/lib/vendor/solr
    $ java -jar start.jar

### Solr in a production environment ###

In a production environment, you should run Solr as a daemon. The right way to do it depends on your server's
system, however, some startup scripts are included in the `lib/vendor/scripts` directory.

On a debian server :

    $ cp plugins/tjSolrDoctrineBehaviorPlugin/lib/vendor/scripts/debian/solr /etc/init.d/solr
    $ chmod 755 /etc/init.d/solr
    $ update-rc.d solr defaults

Before running Solr, just set the PROJECT_NAME value to your symfony project directory at the top of your Solr script.

    PROJECT_NAME=mysfproject

The Solr index will be created in your `data` dir, and the logs will go in `log` dir.

Then you can start the daemon:

    $ /etc/init.d/solr start

Check this URL to be sure everything went fine.

[http://localhost:8983/solr/admin/](http://localhost:8983/solr/admin/)

### About logging

When you start Solr, two kind of log files will be created: Solr log, and Jetty log. With the embedded installation, all logs will be created in `/var/www/youproject/log/solr`.
Don't forget to create this directory.

Solr and Jetty logging are configured in those files:

    plugins/tjSolrDoctrineBehaviorPlugin/lib/vendor/solr/logging.properties
    plugins/tjSolrDoctrineBehaviorPlugin/lib/vendor/solr/etc/jetty.xml

Path are relative, so you really have to start solr from it's base directory.

You can check out some more [documentation about logging here](http://wiki.apache.org/solr/LoggingInDefaultJettySetup).

## How to use ? ##

### Enabling the behavior ###

To index some model objects into Solr, you have to modify your schema.yml file. Add the `Solr` behavior
to the object type you want to index, and define which fields needs to be indexed.

Here's an example schema file:

    [yml]
    Thread:
      columns:
        title:
          type: string(255)
          notnull: true

    Post:
      actAs:
        Solr:
          fields: [ title, body ]
      columns:
        thread_id:
          type: integer
          notnull: true
        title:
          type: string(255)
          notnull: true
        body:
          type: clob
          notnull: true
      relations:
        Thread:
          onDelete: CASCADE
          foreignAlias: Posts

Rebuild your model, load your data, et voilà!
Each time a Post object is created/updated/deleted, the Solr index will be automaticaly updated.

Maybe you don't want your content to be indexed real-time (e.g, if you use data import handler, see below) ?
Set the realtime option to false.

    [yml]
    Post:
      actAs:
        Solr:
          fields: [ title, body ]
          realtime: false

### Field mapping ###

In the previous example, the plugin will try to index the title and body fields into Solr.
You have to manualy define those names in the Solr schema
(in tjSolrDoctrineBehaviorPlugin/lib/vendor/solr/solr/conf/schema.xml), or indexing will fail :

    [xml]
    <field name="title" type="text" indexed="true" stored="true" multiValued="false" />
    <field name="body" type="text" indexed="true" stored="true" multiValued="false" />

You also have to make sure that those fiels are copied in the "sf_text" fields :

    [xml]
    <copyField source="title" dest="sf_text" />
    <copyField source="body" dest="sf_text" />

Configuring each field in the Solr schema can be a pain. That's why this plugin allows you to use
Solr's dynamic fields.

If your model field's name matches some specific pattern, the Solr field will be automaticaly created.
For example, each field suffixed with "_t" will be created with a "text" type, and copied into the "sf_text"
default search field.

You can configure this mapping in the Doctrine schema :

    [yml]
    Post:
      actAs:
        Solr:
          fields: [ title, body ]
          fieldmap: { title: title_t, body: body_t }
          …

Look into the "dynamicField" entries in the Solr's schema to see available patterns.

### Indexing virtual fields ###

Thanks to the Doctrine magic, it is very easy to index virtual fields. All you have to do is
to add a custom getter in the model class. This way, you can even index some relations fields.

Schema using dynamic fields :

    [yml]
    Post:
      actAs:
        Solr:
          fields: [ title, body, threadTitle ]
          fieldmap: { title: title_t, body: body_t, threadTitle: thread_t }

You aren't required to use dynamic fields when defining virtual fields
to index.          
          
Add this getter in the Post class :

    [php]
    public function getThreadTitle()
    {
      return $this->getThread()->getTitle();
    }

Indexing n:m relationships :

    [php]
    public function getThreadTitle()
    {
      $thrArray = array();
      foreach ($this->getThreads() as $thread)
      {
            $thrArray[] = $thread->getTitle();
      }
      return implode($thrArray, ', ');
    }


### Working with I18n ###

This plugin comes with a simple i18n integration. Here's a quick example:

    [yml]
    Story:
      actAs:
        I18n:
          fields: [ body ]
        Solr:
          fields: [ body ]

      columns:
        slug: string(50)
        body:
          type: clob
          notnull: true

The `body` field will be automaticaly indexed as many times as there are defined languages.
Field name will be suffixed with the language in Solr. If you want to perform a search for a
specific language, use an explicit field name in your query.

    [php]
    $story = new Story();
    $story->slug = 'toto';
    $story->Translation['fr']->body = 'Mon histoire';
    $story->Translation['en']->body = 'My story';
    $story->save();

    $results = Doctrine::getTable('Post')->search('body_fr:histoire');
    $results = Doctrine::getTable('Post')->search('body_en:story');
    $results = Doctrine::getTable('Post')->search('sf_text_fr:histoire');
    $results = Doctrine::getTable('Post')->search('sf_text_en:story');

Notice that the default search field is a concatenation of all text fields, and it is optimized to
work with english text. As soon as you index i18n content, you should always explicitly set the language.

Notice also that for i18n fields, the fieldmap option is ignored.

Only fr and en languages are configured in the given Solr schema.xml. You can find [configuration examples
for other languages](http://code.reddit.com/browser/config/solr/schema.xml).

### Connecting to Solr ###

This plugins uses the default Solr connexion parameters. You can override them in the doctrine schema :

    [yml]
    Post:
      actAs:
        Solr:
          fields: [ title, body ]
          fieldmap: { title: title_t, body: body_t }
          host: localhost
          post: 8983
          path: '/solr'
          …

If you have a Solr installation with a multicore index, and want to index differents object types,
just change the "path" parameter for each type.

### Searching ###

Indexing is good, but searching is better.

Once you have attached the behavior to your model, it will provide a "search" method.

    [php]

    // returns true or false wether solr is available or not
    $solrAvailable = Doctrine_Core::getTable('Post')->isSearchAvailable();

    if(!$solrAvailable)
      throw new sfException('Search is unavailable right now. Please come back later');

    $post = new Post();
    $post->title = 'test title';
    $post->body = 'this is my body';
    $post->Thread = new Thread();
    $post->Thread->title = 'test thread';
    $post->save();

    // returns every indexed elements
    $results = Doctrine::getTable('Post')->search('*:*');

    // search in every text fields
    $results = Doctrine::getTable('Post')->search('test');

    // search only in "title" field
    $results = Doctrine::getTable('Post')->search('title_t:test');

    // You can set the offset and limit params
    $results = Doctrine::getTable('Post')->search('*:*', 0, 10);

    // search can take extra params
    $results = Doctrine::getTable('Post')->search('*:*', 0, 10, array('sort' => 'score desc', 'fl' => '*,score'));

The `search` methods retuns a php array corresponding to the Solr xml response.

    [php]
    $results = Doctrine::getTable('Post')->search('*:*');
    var_dump($results);

    array(2) {
      ["responseHeader"]=>
      array(3) {
        ["status"]=>
        int(0)
        ["QTime"]=>
        int(0)
        ["params"]=>
        array(7) {
          ["start"]=>
          string(1) "0"
          ["q"]=>
          string(3) "*:*"
          ["json.nl"]=>
          string(3) "map"
          ["wt"]=>
          string(4) "json"
          ["fq"]=>
          string(18) "sf_meta_class:Post"
          ["version"]=>
          string(3) "1.2"
          ["rows"]=>
          string(2) "30"
        }
      }
      ["response"]=>
      array(3) {
        ["numFound"]=>
        int(1)
        ["start"]=>
        int(0)
        ["docs"]=>
        array(1) {
          [0]=>
          array(7) {
            ["sf_unique_id"]=>
            string(6) "Post_9"
            ["sf_meta_class"]=>
            string(4) "Post"
            ["sf_meta_id"]=>
            int(9)
            ["timestamp"]=>
            string(24) "2010-02-06T15:16:30.523Z"
            ["title_t"]=>
            array(1) {
              [0]=>
              string(5) "title"
            }
            ["sf_text"]=>
            array(2) {
              [0]=>
              string(5) "title"
              [1]=>
              string(4) "body"
            }
            ["body_t"]=>
            array(1) {
              [0]=>
              string(4) "body"
            }
          }
        }
      }
    }

Instead of a dummy php array, the plugin can also generate a Doctrine_Query object,
which you can manipulate as usual. For instance, you can modify sorting options, paginate the list,
add other criterias, etc.

    [php]
    $q = Doctrine::getTable('Post')->createSearchQuery('my query');

    $offset = 10;
    $limit = 50;
    $q2 = Doctrine::getTable('Post')->createSearchQuery('my other query', $offset, $limit);

There is a last available method to performs a search. You can use the `solr:search` task from the command line.

    $ php symfony solr:search Post "my query"
    $ php symfony help solr:search

### Reset index ###

Sometimes, you may want to reset the index and clear all indexed objects. There is a function to do this.

    [php]

    // remove all posts from solr index
    Doctrine_Core::getTable('Post')->deleteIndex();

You can also do this from the command line.

    $ php symfony solr:reset-index Post
    $ php symfony help solr:reset-index

This method will only remove objects from the current class (here: Post).

### Rebuild index ###

A task is available to reindex all objects.

    $ php symfony solr:rebuild-index Post

If you have a lot of objects, you can also set an offset and a limit parameters.

    $ php symfony solr:rebuild-index Post --offset=5 --limit=10


### Using data import handler ###

Using tasks is not the best way to create or reload the index. Instead, use the
[data import handler](http://wiki.apache.org/solr/DataImportHandler).

Once correctly configured, use the following uri to generate the index from scratch:

[http://localhost:8983/solr/dataimport?command=full-import](http://localhost:8983/solr/dataimport?command=full-import)

Each time you access this link, the index will be rebuild. Put this line into your crontab to rebuild your index every hour:

    30 * * * * curl -sS http://localhost:8983/solr/dataimport?command=full-import >/tmp/dataimport.log

If performance matters, use the delta-import command:

[http://localhost:8983/solr/dataimport?command=delta-import](http://localhost:8983/solr/dataimport?command=delta-import)

Note that you have to enable the `Timestampable` template for the delta import to work.


### Transactions ###

Solr supports transactions. By default, the plugins sends a `commit` message after every index operation.
However, you may want to perform many operations in one time, then it is more efficient to send only one
commit when the job's done.

    [php]
    $thread = new Thread();
    $thread->title = 'test tread';
    $tread->save();

    Doctrine_Core::getTable('Post')->beginTransaction();

    for($i = 0 ; $i < 20 ; $i++)
    {
      $post = new Post();
      $post->title = "post $i";
      $post->body = 'post body';
      $post->Thread = $thread
      $post->save();
    }

    // You can use the inTransaction function to know if a transaction exists
    Doctrine_Core::getTable('Post')->inTransaction(); // returns true

    // After the commit, data will be available for searching
    Doctrine_Core::getTable('Post')->commit();

## Testing the plugin ##

This plugins comes with an auto-generated (with the `sfTaskExtraPlugin`) fixtures project, to run the tests
without touching your own app. Tests are not included in the package version, so you'll have to checkout
the trunk version.

Before you run the tests, make sure Solr is running is accessible.
Note that those tests are written to be run with lime 2 (alpha1).

WARNING : By default, the tests will be run over the default Solr index. Make sure you won't lose any data.

Here's how you can easily run the plugin test suite :

  * export the SYMFONY shell var to the symfony lib path. Example :

        $ export SYMFONY='/var/www/myproject/lib/vendor/symfony-1.4.1/lib/'

  * Setup the database for testing

    The plugin's tests are made to run against an independant db. You have to create it before anything else.

        mysql> CREATE DATABASE solr_doctrine_behavior_test;
        mysql> GRANT ALL ON solr_doctrine_behavior_test.* TO test;

    If you want to use an existing database, you have to configure the database connexion by yourself

        $ php plugins/tjSolrDoctrineBehaviorPlugin/test/fixtures/project/symfony configure:database --env=test 'mysql:host=localhost;dbname=solr_doctrine_behavior_test'

  * build the bootstrap project. You don't have to load the fixtures, this will be done on time.

        $ php plugins/tjSolrDoctrineBehaviorPlugin/test/fixtures/project/symfony doctrine:build --env=test --all

  * run the tests

        $ php symfony test:plugin tjSolrDoctrineBehaviorPlugin

    or, if the sfTaskExtraPlugin is not installed :

        $ php plugins/tjSolrDoctrineBehaviorPlugin/test/bin/prove.php

## Professional support ##

I'm a freelance developer, working from France. If you want to contact me for professional support,
use the email you will find in [my profesional page](http://thibault.jouannic.fr). I speak french and english.

## Thanks ##

I'd wish to thank the following persons for contributing to this plugin :

* Guillaume Roques
* Ashton Honnecke
* Robert Gruendler

## TODO ##

Here's some ideas for further developpements:

* Add geolocation search
* add a moreLikeThis() function
