<?php

/**
 * This file is part of Gitonomy.
 *
 * (c) Alexandre Salomé <alexandre.salome@gmail.com>
 * (c) Julien DIDIER <genzo.wm@gmail.com>
 *
 * This source file is subject to the GPL license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gitonomy\Bundle\WebsiteBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    protected $client;

    public function setUp()
    {
        $this->client = self::createClient();
        $this->client->startIsolation();
    }

    public function tearDown()
    {
        $this->client->stopIsolation();
    }

    public function testSshKeyListAndCreate()
    {
        $this->client->connect('bob');

        $crawler  = $this->client->request('GET', '/profile/ssh-keys');
        $response = $this->client->getResponse();

        // List
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(2, $crawler->filter('.ssh-key')->count());
        $this->assertEquals(1, $crawler->filter('.ssh-key h3:contains("Installed key")')->count());
        $this->assertEquals(0, $crawler->filter('.ssh-key h3:contains("Installed key") + pre + p span.label-info')->count());
        $this->assertEquals(1, $crawler->filter('.ssh-key h3:contains("Not installed key")')->count());
        $this->assertEquals(1, $crawler->filter('.ssh-key h3:contains("Not installed key") span span.label-info')->count());

        // Create
        $form = $crawler->filter('form')->form(array(
            'profile_ssh_key[title]'   => 'foo',
            'profile_ssh_key[content]' => 'bar'
        ));

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect('/profile/ssh-keys'));

        $crawler  = $this->client->followRedirect();

        $this->assertEquals(3, $crawler->filter('.ssh-key')->count());

        $this->assertEquals(1, $crawler->filter('.ssh-key h3:contains("foo")')->count());
        $this->assertEquals(1, $crawler->filter('.ssh-key h3:contains("foo") span span.label-info')->count());
    }

    public function testSshKeyCreateInvalid()
    {
        $this->client->connect('bob');

        $crawler  = $this->client->request('GET', '/profile/ssh-keys');
        $response = $this->client->getResponse();

        // Create
        $form = $crawler->filter('form')->form(array(
            'profile_ssh_key[title]'   => 'foo',
            'profile_ssh_key[content]' => 'bar'."\n"."baz"
        ));

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertFalse($response->isRedirect());

        $this->assertEquals("No newline permitted", $crawler->filter('#profile_ssh_key_content + div span.help-inline')->text());
    }

    public function testChangeWrongUsername()
    {
        $this->markTestSkipped();

        $this->client->connect('bob');

        $crawler  = $this->client->request('GET', '/profile/change-username');
        $response = $this->client->getResponse();

        $form = $crawler->filter('form input[type=submit]')->form(array(
            'change_username[username]' => 'foo bar'
        ));

        $crawler  = $this->client->submit($form);

        $this->assertFalse($response->isRedirect());

        $this->assertEquals(1, $crawler->filter('#change_username_username_field.error')->count());
    }

    public function testActivate()
    {
        $this->markTestSkipped();

        $em   = $this->client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository('GitonomyCoreBundle:User')->findOneByUsername('inactive');

        $crawler = $this->client->request('GET', '/profile/'.$user->getUsername().'/activate/'.$user->getActivationToken());

        $form = $crawler->filter('#user input[type=submit]')->form(array(
            'change_password[password][first]'  => 'inactive',
            'change_password[password][second]' => 'inactive',
        ));

        $crawler  = $this->client->submit($form);

        $this->client->connect('inactive');
    }
}
