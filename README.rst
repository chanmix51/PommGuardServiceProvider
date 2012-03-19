========================
PommGuardServiceProvider
========================

**== IMPORTANT ==** 

This is alpha software and it does not work nor have tests for now. It should be used by developers only. 

************
Installation
************

There'll be some instructions when the package is not alpha.

*****
Setup
*****

Database
========
In psql, import the ``sql/tables.sql`` script. It will create the ``pomm_guard`` schema with the tables needed. 

::

          Table "pomm_guard.pomm_user"
    Column  |        Type         | Modifiers 
  ----------+---------------------+-----------
   login    | character varying   | not null
   password | character varying   | not null
   groups   | character varying[] | 
  Indexes:
      "pomm_user_pkey" PRIMARY KEY, btree (login)
  Check constraints:
      "check_groups" CHECK (groups_exist(groups))
  
           Table "pomm_guard.pomm_group"
     Column    |        Type         | Modifiers 
  -------------+---------------------+-----------
   name        | character varying   | not null
   credentials | character varying[] | 
  Indexes:
      "pomm_group_pkey" PRIMARY KEY, btree (name)
 

If you want to enable the automatic encryption, you must include the ``trigger.sql`` script. It requires the ``plpgsql`` language and the ``pgcrypto`` extension to be installed in the current database. Note you must be database superuser to do so.

::

  =# CREATE LANGUAGE plpgsql;
  CREATE LANGUAGE
  =# -- PG 9.1
  =# CREATE EXTENSION pgcrypto;
  CREATE EXTENSION
  =# -- <= PG 9.0
  =# \i /usr/share/postgresql/8.4/contrib/pgcrypto.sql

Silex
=====

The following services must be registered before the ``PommGuardServiceProvider`` service:
 * SessionServiceProvider
 * PommServiceProvider

 ::

    $app->register(new \GHub\Silex\PommGuard\PommGuardServiceProvider());

An instance of the user map class will be instanciated with a new connection from the default database. You can enforce an existing connection::

    $app->register(new \GHub\Silex\PommGuard\PommGuardServiceProvider(), array(
        'pomm_guard.config.connection' => $app['pomm']->getDatabase('plop')
            ->createConnection()
    ));

This is useful when you want to use custom database or query filters like the logger.

******************
Using the provider
******************

Simple use
==========
If the provided ``pomm_user`` and ``pomm_group`` tables fit your needs, you do not need anything to add in the database. If you want to enable automatic password encryption, you must create a trigger on the ``pomm_guard.pomm_user`` table::

    CREATE TRIGGER before_insert_update_pomm_user
      BEFORE UPDATE OR INSERT ON pomm_guard.pomm_user
      FOR EACH ROW EXECUTE PROCEDURE pomm_guard.pomm_user_encrypt_password();

This way, all you have got to do is to insert or update rows giving plain text passwords, they will be encrypted on the fly::

    $map = $connection
      $->getMapFor('GHub\Silex\PommGuard\Model\PommUser');
    
    $user = $map->createObject(array(
        'login'     => 'pika',
        'password'  => 'chu'
    ));
    $map->saveOne($user);    

    // This should retrieve the user
    //
    // using automatic encryption:
    $auth_user = $map->checkPassword(
        array('login' => 'pika'),
        'chu');

    // using plain text:
    $auth_user = $map->checkPassword(
        array('login' => 'pika'),
        'chu',
        true);
    
In the database you should have something like the following::

    =$ SELECT * FROM pomm_guard.pomm_user;
     login |              password              | groups 
    -------+------------------------------------+--------
     pika  | $1$fujKjHzg$IiAzmkm2SBLO/FqjuxFDZ0 | 
    (1 row)

Note that the password is **removed from the fields returned by your SELECT statements** so unless you specify differently, ``$user['password']`` will not exist when fetched from the database.

PommGuard provides you with several functions to be used as middleware for your controllers::

must_be_authenticated() 
    return a redirection to ``$app['pomm_guard.config.login_url']`` (default ``/login``) if the current session is NOT authenticated.

must_not_be_authenticated()
    return a redirection to ``$app['pomm_guard.config.login_url']`` (default ``/login``) if the current session IS authenticated.

::

    // This controller is protected from non authenticated access.
    $app->get('/protected/url', function() use ($app) { 
      ...
    })->middleware($app['pomm_guard.must_be_authenticated']);


The service provider overrides the normal ``Session`` instance with its own. This class adds several methods dedicated to use with authentication and Pomm:

setUserMap(BaseObjectMap $instance)♢
    Called in the ``register()`` method.
setPommUser(Model\PommUser $user)♢
    Attach a user with the session.
removePommUser()♢
    Remove the user from session.
getPommUser()♢
    Retrieve the user from session.
authenticate($authenticate)♢
    Set authenticated (true or false).
isAuthenticated()♢
    Get session authenticated state.
hasCredential($credential)♢
    Return true if given credential is set to the attached user.
hasCredentials(Array $credentials)♢
    Return true if all given credentials are set to the attached user.

A default login controller would be like::

    $app->post('/login', function() use ($app) {
        if ($app['request']->request->has('login')) {
            $login = $app['request']->request->get('login');
            $user = $app['pomm.connection']
                ->getMapFor('Db\Schema\YourUser')
                ->checkPassword(array('login' => $login['email']), $login['password']);

            if (!is_null($user)) {
                $app['session']->setPommUser($user);
                $app['session']->authenticate(true);

                return $app->redirect($app['url_generator']->generate('index'));
                }
            }

        return $app['twig']->render('login.html.twig', array('error_msg' => 'No such user or password'));
    });


Extending the model
===================

Let's take a more complexe case, imagine users are identified with their login and their department info plus we want to be able to store key value informations (needs hstore extension and according pomm converter registered to the database, see `Pomm's documentation <http://pomm.coolkeums.org/documentation/manual#registering-converters>`_)::

    =$ CREATE TABLE my_app.app_user (
          dept char(3), 
          extra_infos hstore, 
          primary key(login,dept)
       ) 
       INHERITS (pomm_guard.pomm_user);
    CREATE TABLE
    =$ \d my_user
                Table "my_app.my_user"
       Column   |        Type         | Modifiers 
    ------------+---------------------+-----------
     login      | character varying   | not null
     password   | character varying   | not null
     groups     | character varying[] | 
     dept       | character(3)        | not null
     extra_info | hstore              | 
    Indexes:
        "my_user_pkey" PRIMARY KEY, btree (login, dept)
    Check constraints:
        "check_groups" CHECK (pomm_guard.groups_exist(groups))
    Inherits: pomm_guard.pomm_user

When generating the model files, you must specifically rebuild the base file for your users and/or groups to tell Pomm that parents namespace cannot be guessed from the database information::

    $scan = new Pomm\Tools\CreateBaseMapTool(array(
        'schema' => 'my_app',
        'table'  => 'my_user',
        'database' => $app['pomm']->getDatabase(),
        'prefix_dir' => PROJECT_DIR.'/sources/model',
        'parent_namespace' => '\GHub\Silex\PommGuard\Model'
        ));

By default, entity classes extend ``Pomm\Object\BaseObject``, change ``MyUser`` class to extend ``\GHub\Silex\PommGuard\Model\PommUser`` and you're done.
