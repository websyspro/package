<?php

namespace Websyspro\Package;

use Websyspro\Package\Interfaces\CMDStructure;
use Websyspro\Package\Interfaces\PackageStructure;
use function sprintf;

class UpdateComposer
{
  private string $composerFile;
  private array $composerStructure;

  public function __construct(
    public string $directory
  ){
    $this->composerFile();
    $this->composerLoader();
    $this->composerSaveVersion();
    $this->composerSendVersion();
  }

  private function write(
    string $data
  ): void {
    fwrite( STDOUT, $data );
  }

  private function package(
  ): PackageStructure {
    return new PackageStructure(
      ...[
        $this->composerStructure[ ConstsComposer::name ],
        $this->composerStructure[ ConstsComposer::description ],
        ...explode( 
          ConstsComposer::versionSeparator, $this->composerStructure[
            ConstsComposer::version 
          ] ?? ConstsComposer::versionDefault
        )
      ]
    );
  }

  private function composerFile(
  ): void {
    $this->composerFile = sprintf(
      "%s/%s", rtrim(
        $this->directory, "/\\"
      ), "composer.json"
    );
  }

  private function composerLoader(
  ): void {
    if( file_exists( $this->composerFile) === false ){
      $this->write( "\033[31mComposer file not found\033[0m" );
    } else {
      $this->composerStructure = json_decode(
        file_get_contents( $this->composerFile ), true
      );
    }
  }

  private function composerSaveVersion(
  ): void {
    $this->composerStructure[
      ConstsComposer::version
    ] = $this->package()->versionInc();

    $content = json_encode( 
      $this->composerStructure, 
      JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    $content = str_replace( "    ", "  ", $content );
    file_put_contents( $this->composerFile, $content . PHP_EOL );
  }

  private function shellExec(
    CMDStructure $cmd
  ): void {
    exec(
      strtoupper( substr( PHP_OS, 0, 3 )) === "WIN"
        ? "{$cmd->script} >nul 2>&1" 
        : "{$cmd->script} >/dev/null 2>&1", 
      $output, $result_code
    );

    if( $result_code !== 0 ){
      exit( $result_code );
    } else {
      $this->write( "\r\033[K GIT / \033[32m{$cmd->hits}\033[0m" );
    }
  }

  private function composerSendVersion(
  ): void {
    $package = $this->package();

    $this->write( "\n\033[1mPackage Version Manager\033[0m\n" );
    $this->write( "\n\033[2mPackage name: \033[0m\033[32m{$package->name()} \033[1mv{$package->version()}\033[0m" );
    $this->write( "\n\033[2mPackage description: \033[0m\033[32m{$package->description()}\n\n\033[?25l" );

    foreach([ 
      new CMDStructure( "git add .", "stage all changes" ),
      new CMDStructure( "git commit -m \"Release {$package->version()}\"", "create release commit" ),
      new CMDStructure( "git tag {$package->version()}", "create release tag" ),
      new CMDStructure( "git push origin HEAD", "push commit to origin" ),
      new CMDStructure( "git push origin {$package->version()}", "push release tag to origin" )
    ] as $structure ){
      $this->shellExec( $structure );
    }

    $this->write( "\r\033[K\033[?25h\033[36mPublish finish v{$package->version()}\033[0m\n" );
  }
}
