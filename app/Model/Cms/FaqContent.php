<?php

declare(strict_types=1);

namespace App\Model\Cms;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s FAQ.
 */
#[ORM\Entity]
#[ORM\Table(name: 'faq_content')]
class FaqContent extends Content implements IContent
{
    protected string $type = Content::FAQ;
}
