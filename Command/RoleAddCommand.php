<?php

namespace Grypho\SecurityBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Grypho\SecurityBundle\Entity\Role;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class RoleAddCommand extends Command
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
                ->setName('security:role:add')
                ->setDescription('Add another role.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $question = new Question('Name of new Role: ');
        $question->setValidator(function ($answer) {
            if (strlen($answer) < 3 || 'ROLE_' !== substr($answer, 0, 5)) {
                throw new \RuntimeException(
                'A role needs at least three characters and has to end with _ROLE'
                );
            }

            return $answer;
        });
        $name = $helper->ask($input, $output, $question);

        $question2 = new Question('Description of the role: ');
        $desc = $helper->ask($input, $output, $question2);

        $role = new Role();
        $role->setName($desc);
        $role->setRole($name);

        $this->em->persist($role);
        $this->em->flush();

        $output->writeln('Created role '.$role->getRole().'.');

        return 0;
    }
}
