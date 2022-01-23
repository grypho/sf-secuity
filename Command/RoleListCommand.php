<?php

namespace Grypho\SecurityBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RoleListCommand extends Command
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
            ->setName('security:role:list')
            ->setDescription('List roles of a user if username given, show list of roles otherwise')
            ->addArgument('username', InputArgument::OPTIONAL, 'Welchen Benutzernamen hinzufügen?')
        ;
    }

    protected function listRolesUser($username, OutputInterface $output)
    {
        $repo = $this->em->getRepository('GryphoSecurityBundle:User');

        $user = $repo->findOneByUsername($username);
        $output->writeln('Roles of user '.$user->getFullName().' ('.$user->getUsername().'):');
        $roles = $user->getRoles();
        foreach ($roles as $role) {
            $output->writeln("\t".$role->getRole().' ('.$role->getName().')');
        }

        if (!count($roles)) {
            $output->writeln("\t[no roles assigned!]");
        }
    }

    protected function listAllRoles(OutputInterface $output)
    {
        $repo = $this->em->getRepository('GryphoSecurityBundle:Role');

        $output->writeln('Available roles:');
        $roles = $repo->findAll();
        foreach ($roles as $role) {
            $output->writeln("\t".$role->getRole().' ('.$role->getName().')');
        }

        if (!count($roles)) {
            $output->writeln("\t[no roles defined!]");
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        if ($username) {
            $this->listRolesUser($username, $output);
        } else {
            $this->listAllRoles($output);
        }

        return 0;
    }
}
