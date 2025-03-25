<?php

namespace App\Tests;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UtilisateurRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Ensure we have a clean database
        $container = static::getContainer();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $this->userRepository = $container->get(UtilisateurRepository::class);

        foreach ($this->userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();
    }

    public function testRegister(): void
    {
        // Register a new user
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Inscription');

        $crawler = $this->client->submitForm('Inscription', [
            'registration_form[email]' => 'me@example.com',
            'registration_form[plainPassword]' => 'password123',
        ]);

        // Ensure the response redirects after submitting the form, the user exists, and is not verified
        self::assertCount(1, $this->userRepository->findAll());
        self::assertResponseRedirects('/');
    }
}
