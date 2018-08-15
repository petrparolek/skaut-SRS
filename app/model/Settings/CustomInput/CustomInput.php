<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * Abstraktní entita vlastní pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="CustomInputRepository")
 * @ORM\Table(name="custom_input")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "custom_checkbox" = "CustomCheckbox",
 *     "custom_text" = "CustomText",
 *     "custom_select" = "CustomSelect",
 *     "custom_file" = "CustomFile"
 * })
 */
abstract class CustomInput
{
    /**
     * Textové pole.
     */
    public const TEXT = 'text';

    /**
     * Zaškrtávací pole.
     */
    public const CHECKBOX = 'checkbox';

    /**
     * Výběrové pole.
     */
    public const SELECT = 'select';

    /**
     * Soubor.
     */
    public const FILE = 'file';

    public static $types = [
        self::TEXT,
        self::CHECKBOX,
        self::SELECT,
        self::FILE,
    ];

    /**
     * Typ vlastního pole.
     */
    protected $type;

    use Identifier;

    /**
     * Název vlastního pole.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Povinné pole.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $mandatory = false;

    /**
     * Pořadí pole na přihlášce.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position;

    /**
     * Hodnoty pole pro jednotlivé uživatele.
     * @ORM\OneToMany(targetEntity="\App\Model\User\CustomInputValue\CustomInputValue", mappedBy="input", cascade={"persist"})
     * @var Collection
     */
    protected $customInputValues;


    public function __construct()
    {
        $this->customInputValues = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function isMandatory() : bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory) : void
    {
        $this->mandatory = $mandatory;
    }

    public function getPosition() : int
    {
        return $this->position;
    }

    public function setPosition(int $position) : void
    {
        $this->position = $position;
    }

    /**
     * @return mixed
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return Collection
     */
    public function getCustomInputValues() : Collection
    {
        return $this->customInputValues;
    }
}
