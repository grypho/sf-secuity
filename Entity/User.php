<?php

// src/Acme/UserBundle/Entity/User.php

namespace Grypho\SecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface; // Signup form validation
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="gsb_user")
 * @UniqueEntity(fields="email", message="Die Mailadresse wurde bereits registriert.")
 * @UniqueEntity("username", message="Der Benutzername wird bereits verwendet.")
 */
class User implements UserInterface, EquatableInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     * @Assert\Regex(
     *     pattern="/^[0-9a-zA-Z\.]+$/",
     *     match=true,
     *     message="Bitte nur Buchstaben und Ziffern verwenden"
     * )
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=80)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     * @Assert\Email(
     *     message = "The E-Mail Adresse '{{ value }}' ist keine gültige Adresse."
     * )
     */
    private $email;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt = null;

    /**
     * @ORM\Column(name="lastactivity_at", type="datetime", nullable=true)
     */
    private $lastactivityAt = null;

    /**
     * @ORM\Column(name="login_count", type="integer", nullable=false)
     */
    private $loginCount = null;

    /**
     * @ORM\Column(name="login_failed_count", type="integer", nullable=false)
     */
    private $loginFailedCount = null;

    /**
     * @ORM\Column(name="one_time_token", type="integer", nullable=true)
     */
    private $oneTimeToken = null;

    /**
     * @ORM\Column(name="oauth_token", type="string", nullable=true)
     */
    private $oauth_token = null;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     * @ORM\JoinTable(name="gsb_user_role")
     */
    private $roles;

    public function __construct()
    {
        $this->isActive = true;
        // may not be needed, see section on salt below
        $this->salt = md5(uniqid(null, true));
        $this->roles = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->loginCount = 0;
        $this->loginFailedCount = 0;
    }

    public function __toString()
    {
        return $this->username;
    }

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Regex(
     *     pattern="/^\S{2,}/",
     *     match=true,
     *     message="Der Vorname muss aus mindestens zwei Zeichen bestehen"
     * )
     */
    protected $name_first;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name_middle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name_last;

    public function getFullName()
    {
        return $this->getNameFirst().' '.($this->getNameMiddle() ? $this->getNameMiddle().' ' : '').$this->getNameLast();
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ]);
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function isOauth()
    {
        return $this->getOauthToken() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }

    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->roles as $r) {
            $roles[] = $r->getRole();
        }

        return array_unique($roles);
//        return $this->roles->toArray();
    }

    public function setPasswordEncrypt($plainPassword)
    {
        global $kernel;

        if ('AppCache' == get_class($kernel)) {
            $kernel = $kernel->getKernel();
        }

        // see http://symfony.com/doc/current/book/security.htmlhttp://symfony.com/doc/current/book/security.html
        $encoder = $kernel->getContainer()->get('security.password_encoder');

        $encoded = $encoder->encodePassword($this, $plainPassword);

        return $this->setPassword($encoded);
    }

    public function getPasswordEncrypt()
    {
        return ''; // We cannot retrieve encrypted passwords.
    }

    public function isEqualTo(UserInterface $user)
    {
        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;
        $this->clearOneTimeToken(); // OneTimeToken vom "Password-reset" entfernen

        return $this;
    }

    public function shouldChangePassword()
    {
        return $this->getOneTimeToken() !== null;
    }

    /** =========== AUTOGENERATED ENTRIES BEYOND THIS POINT! =========== **/

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set isActive.
     *
     * @param bool $isActive
     *
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set name_first.
     *
     * @param string $nameFirst
     *
     * @return User
     */
    public function setNameFirst($nameFirst)
    {
        $this->name_first = $nameFirst;

        return $this;
    }

    /**
     * Get name_first.
     *
     * @return string
     */
    public function getNameFirst()
    {
        return $this->name_first;
    }

    /**
     * Set name_last.
     *
     * @param string $nameLast
     *
     * @return User
     */
    public function setNameLast($nameLast)
    {
        $this->name_last = $nameLast;

        return $this;
    }

    /**
     * Get name_last.
     *
     * @return string
     */
    public function getNameLast()
    {
        return $this->name_last;
    }

    /**
     * Add roles.
     *
     * @return User
     */
    public function addRole(\Grypho\SecurityBundle\Entity\Role $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles.
     */
    public function removeRole(\Grypho\SecurityBundle\Entity\Role $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set lastactivityAt.
     *
     * @param \DateTime $lastactivityAt
     *
     * @return User
     */
    public function setLastactivityAt($lastactivityAt)
    {
        $this->lastactivityAt = $lastactivityAt;

        return $this;
    }

    /**
     * Get lastactivityAt.
     *
     * @return \DateTime
     */
    public function getLastactivityAt()
    {
        return $this->lastactivityAt;
    }

    /**
     * Set loginCount.
     *
     * @param int $loginCount
     *
     * @return User
     */
    public function setLoginCount($loginCount)
    {
        $this->loginCount = $loginCount;

        return $this;
    }

    /**
     * Get loginCount.
     *
     * @return integer
     */
    public function getLoginCount()
    {
        return $this->loginCount;
    }

    /**
     * Set loginFailedCount.
     *
     * @param int $loginFailedCount
     *
     * @return User
     */
    public function setLoginFailedCount($loginFailedCount)
    {
        $this->loginFailedCount = $loginFailedCount;

        return $this;
    }

    /**
     * Get loginFailedCount.
     *
     * @return integer
     */
    public function getLoginFailedCount()
    {
        return $this->loginFailedCount;
    }

    public function getOneTimeToken()
    {
        return $this->oneTimeToken;
    }

    public function generateOneTimeToken()
    {
        $this->oneTimeToken = random_int(0, 1 << 30);
    }

    public function clearOneTimeToken()
    {
        $this->oneTimeToken = null;
    }

    /**
     * Set oneTimeToken.
     *
     * @param int $oneTimeToken
     *
     * @return User
     */
    public function setOneTimeToken($oneTimeToken)
    {
        $this->oneTimeToken = $oneTimeToken;

        return $this;
    }

    /**
     * Set oauthToken.
     *
     * @param string $oauthToken
     *
     * @return User
     */
    public function setOauthToken($oauthToken)
    {
        $this->oauth_token = $oauthToken;

        return $this;
    }

    /**
     * Get oauthToken.
     *
     * @return string
     */
    public function getOauthToken()
    {
        return $this->oauth_token;
    }

    /**
     * Set nameMiddle.
     *
     * @param string $nameMiddle
     *
     * @return User
     */
    public function setNameMiddle($nameMiddle)
    {
        $this->name_middle = $nameMiddle;

        return $this;
    }

    /**
     * Get nameMiddle.
     *
     * @return string
     */
    public function getNameMiddle()
    {
        return $this->name_middle;
    }
}
