<?php

namespace Websyspro\Package\Interfaces;

use Websyspro\Package\ConstsComposer;

class PackageStructure
{
  public function __construct(
    public readonly string $name,
    public readonly string $description,
    public readonly int $major,
    public readonly int $minor = 0,
    public readonly int $patch = 0
  ){}

  public function name(
  ): string {
    return $this->name;
  }

  public function description(
  ): string {
    return $this->description;
  }  

  public function version(
  ): string {
    return implode(
      ConstsComposer::versionSeparator, [
        $this->major, $this->minor, $this->patch
      ]
    );
  }  

  public function versionInc(
  ): string {
    return implode(
      ConstsComposer::versionSeparator, [
        $this->major, $this->minor, $this->patch + 1
      ]
    );
  }
}