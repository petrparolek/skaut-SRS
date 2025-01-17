<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s přehledem lektorů.
 */
interface ILectorsContentControlFactory
{
    public function create(): LectorsContentControl;
}
