<?php

namespace Grypho\SecurityBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RoleAssignCommand extends Command
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
                ->setName('security:role:assign')
                ->addArgument('username', InputArgument::REQUIRED, 'Welchen Benutzernamen ändern?')
                ->addArgument('role', InputArgument::REQUIRED, 'Neue Rolle')
                ->setDescription('Assign role to user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $this->em
                ->getRepository('GryphoSecurityBundle:User')
                ->findOneByUsername($input->getArgument('username'));

        $role = $this->em
                ->getRepository('GryphoSecurityBundle:Role')
                ->findOneByRole($input->getArgument('role'));

        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        if (!$role) {
            throw new \RuntimeException('Role not found');
        }
        $user->addRole($role);
        $this->em->flush();

        $output->writeln('Assigned role '.$role->getRole().' to '.$user->getUsername().'.');

        return 0;
    }
}
