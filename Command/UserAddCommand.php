<?php

namespace Grypho\SecurityBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Grypho\SecurityBundle\Entity\User;
use Grypho\SecurityBundle\Lib\PasswordGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserAddCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('security:user:add')
            ->setDescription('Add user')
            ->addArgument('username', InputArgument::REQUIRED, 'Welchen Benutzernamen hinzufügen?')
            ->addArgument('email', InputArgument::REQUIRED, 'Mailadresse')
            ->addArgument('password', InputArgument::OPTIONAL, 'Welches Passwort?')
            ->addOption('notify', null, InputOption::VALUE_NONE, 'Inform user using email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->em->getRepository('GryphoSecurityBundle:User');

        $user = new User();

        $password = $input->getArgument('password');
        if (!$password) {
            $password = PasswordGenerator::GeneratePassword();
        }

        $user->setUsername($input->getArgument('username'));
        $user->setEmail($input->getArgument('email'));
        $user->setPasswordEncrypt($password);
        $user->generateOneTimeToken(); // damit es direkt nach dem Login zum Passwort-ändern geht

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('Created user '.$user->getUsername().'.');

        return 0;
    }
}
