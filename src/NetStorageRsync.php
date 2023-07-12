<?php

namespace AkamaiIntegration;

use PhpRedisQueue\QueueWorker;
use Psr\Log\LoggerInterface;

class NetStorageRsync
{
  /**
   * @param string $host      Akamai NetStorage hostname
   * @param string $directory Directory on host where the files are stored
   * @param string $username  Akamai NetStorage username
   * @param string $password  Akamai NetStorage password
   */
  public function __construct(
    protected string $host,
    protected string $akamaiRootDirectory,
    protected string $username,
    protected string $password,
    protected        $logger = null
  )
  {
    putenv("RSYNC_PASSWORD={$this->password}");

    if (!str_contains($this->host, 'akamai') && substr($this->akamaiRootDirectory, 0, 1) !== '/') {
      throw new \InvalidArgumentException('The Akamai root directory must be an absolute path.');
    }

    // remove trailing slash (we'll add this later)
    if (substr($this->akamaiRootDirectory, -1) === '/') {
      $this->akamaiRootDirectory = rtrim($this->akamaiRootDirectory, '/');
    }
  }

  /**
   * Upload a file.
   * In the examples, the full path of the file to be uploaded is:
   * /var/www/html/project/uploads/2023-05/filename.jpg
   * @param string $basepath System path that cooresponds to Akamai upload directory.
   *                         Example: /var/www/html/project
   *                         Akamai upload directory will contain content in `project` directory
   * @param string $filepath Filepath relative to basepath
   *                         Drupal example: uploads/2023-05/filename.jpg
   * @param bool   $dryRun   Generate the command in dry-run mode (true) or not (false)
   * @return int
   * @throws \ErrorException
   */

  /**
   * @param string $sourceDirectory
   * @param string $destinationDirectory
   * @param array  $files
   * @param bool   $dryRun
   * @return int
   */
  public function upload(string $sourceDirectory, string $destinationDirectory, array $files = [], bool $dryRun = false): int
  {
    return $this->rsync($sourceDirectory, $destinationDirectory, $files, false,$dryRun);
  }

  protected function rsync(string $sourceDirectory, string $destinationDirectory, array $files = [], bool $delete = false, bool $dryRun = false)
  {
    if (empty($files)) {
      throw new \ErrorException('List of files to include cannot be empty.');
    }

    $command = $this->compileCommand($sourceDirectory, $destinationDirectory, $files, $delete,$dryRun);

    exec($command, $output, $resultCode);

    if ($dryRun && $this->logger) {
      $this->logger->info('NetStorage RSYNC dry run', [
        'command' => $command,
        'output' => $output,
      ]);
    }

    if ($resultCode > 0) {
      throw new \ErrorException('NetStorage RSYNC failed. ' . implode('\n', $output));
    }

    return $resultCode;
  }

  public function compileCommand(string $sourceDirectory, string $destinationDirectory, array $files = [], bool $delete = false, bool $dryRun = false)
  {
    $include = array_map(function ($filename) {
      $sanitized = addcslashes($filename, '"');
      return "--include=\"{$sanitized}\"";
    }, $files);

    $command = [
      'rsync -a',
      $dryRun ? '--dry-run --verbose' : '',
      $delete ? '--delete' : '',
      implode(' ', $include),
      '--exclude="*"',
      $this->standardizeDirectory($sourceDirectory),
      $this->getDestination($destinationDirectory),
      '2>&1', // redirect to STDOUT (php can capture this)
    ];

    return implode(' ', array_filter($command));
  }

  protected function standardizeDirectory($dir)
  {
    if (empty($dir)) {
      return $dir;
    }

    // make sure source has trailing /
    if (substr($dir, -1) !== '/') {
      $dir .= '/';
    }

    return $dir;
  }

  protected function getDestination(string $destinationDirectory)
  {
    $destinationDirectory = $this->standardizeDirectory($destinationDirectory);
    $dir = $this->akamaiRootDirectory . '/' . $destinationDirectory;

    if ($this->host && $this->username) {
      if (str_contains($this->host, 'akamai')) {
        // daemon syntax for akamai
        return "$this->username@$this->host::{$this->username}/{$dir}";
      } else {
        // testing on another server (like a staging server)
        return "$this->username@$this->host:{$dir}";
      }
    }

    // testing locally
    return $dir;
  }
}
