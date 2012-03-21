<?php

namespace GHub\Silex\PommGuard\Tests;

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use GHub\Silex\PommGuard\PommGuardServiceProvider;
use GHub\Silex\PommGuard\Session;
use GHub\Silex\PommGuard\Model\PommUser;
use GHub\Silex\PommGuard\Model\PommGroup;

use GHub\Silex\Pomm\PommServiceProvider;

use \Pomm\FilterChain\LoggerFilter;
use \Pomm\Tools\Logger;
use \Pomm\Connection\Database;
use \Pomm\Exception\Exception as PommException;

/**
 * Session test cases.
 *
 * @Grégoire Hubert <hubert.greg@gmail.com>
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{

    protected $connection;
    protected $logger;
    protected $map_user;
    protected $map_group;

    public function tearDown()
    {
        $db = $this->connection->getDatabase();
        $db->executeAnonymousQuery('TRUNCATE pomm_guard.pomm_user');
        $db->executeAnonymousQuery('TRUNCATE pomm_guard.pomm_group');
    }
    public function setUp()
    {
        $db = new Database(array('name' => 'test', 'dsn' => $GLOBALS['dsn']));
        $this->logger = new Logger();
        $this->connection = $db->createConnection()
            ->registerFilter(new LoggerFilter($this->logger));

        $this->map_user = $this->connection
            ->getMapFor('GHub\Silex\PommGuard\Model\PommUser');

        $this->map_group = $this->connection
            ->getMapFor('GHub\Silex\PommGuard\Model\PommGroup');

        try {
            if (!$user = $this->map_user->findByPk(array('login' => 'test_user'))) {

                $user = new PommUser(array('login' => 'test_user', 'password' => 'plop'));
                $this->map_user->saveOne($user);
            }

            $groups = $this->map_group->findAll();

            if ($groups->count() == 0) {
                for($i = 0; $i <= 9; $i++) {
                    $group = new PommGroup(array('name' => sprintf('grp_%d', $i), 'credentials' => range(0, $i)));
                    $this->map_group->saveOne($group);
                }
            }
        } catch (PommException $e) {
            $logs = array_map(function($val) { return sprintf("SQL=%s\n", $val['sql']); }, $this->logger->getLogs());

            throw new \Exception(sprintf("%s\n===\nSQL DUMP\n%s", $e->getMessage(), join("\n===\n", $logs)));
        }
    }

    public function getPommUser()
    {
        return array(array(new PommUser(array('login' => 'test_user'))));
    }

    /**
     * @dataProvider getPommUser
     **/
    public function testSetPommUser($user)
    {
        $session = new Session($this->map_user, new MockArraySessionStorage());
        $session->setPommUser($user);

        $this->assertArrayHasKey('login', $session->get('_pg_guard_user'));
        $this->assertEquals('test_user', reset($session->get('_pg_guard_user')));
    }

    /**
     * @dataProvider getPommUser
     **/
    public function testGetPommUser($user)
    {
        $storage = new MockArraySessionStorage();
        $session = new Session($this->map_user, $storage);

        $session->setPommUser($user);
        $new_user = $session->getPommUser();

        $this->assertTrue($new_user instanceof \GHub\Silex\PommGuard\Model\PommUser);
        $this->assertEquals($new_user['login'], 'test_user');

        $session = new Session($this->map_user, $storage);
        $session->set('_pg_guard_user', array('login' => 'test_user'));
        $user = $session->getPommUser();

        $this->assertTrue($user instanceof \GHub\Silex\PommGuard\Model\PommUser);
        $this->assertEquals($user['login'], 'test_user');
    }


    /**
     * @dataProvider getPommUser
     **/
    public function testRemovePommUser($user)
    {
        $session = new Session($this->map_user, new MockArraySessionStorage());
        $session->setPommUser($user);
        $session->removePommUser();
        $this->assertFalse($session->has('_pg_guard_user'));
    }

    /**
     * @dataProvider getPommUser
     **/
    public function testAuthenticate($user)
    {
        $session = new Session($this->map_user, new MockArraySessionStorage());
        $this->assertFalse($session->has('_pg_is_authenticated'));
        $session->authenticate(true);
        $this->assertTrue($session->has('_pg_is_authenticated'));
        $this->assertTrue($session->get('_pg_is_authenticated'));
        $session->authenticate(false);
        $this->assertFalse($session->has('_pg_is_authenticated'));
    }

    /**
     * @dataProvider getPommUser
     **/
    public function testHasCredentials($user)
    {
        $creds = range(0,6);

        $storage = new MockArraySessionStorage();
        $session = new Session($this->map_user, $storage);
        $session->setPommUser($user);
        $this->assertFalse($session->hasCredentials($creds));

        $user->groups = array('grp_0', 'grp_2', 'grp_4', 'grp_6');
        $this->map_user->updateOne($user, array( 'groups'));

        $session = new Session($this->map_user, $storage);
        $session->set('_pg_guard_user', array('login' => 'test_user'));
        $this->assertTrue($session->hasCredentials($creds));
    }
}
