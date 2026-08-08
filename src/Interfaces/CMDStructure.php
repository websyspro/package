<?php

namespace Websyspro\Package\Interfaces;

class CMDStructure
{
  public function __construct(
    public readonly string $script,
    public readonly string $hits
  ){}
}