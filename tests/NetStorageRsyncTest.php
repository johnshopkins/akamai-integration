<?php

namespace AkamaiIntegration;

class NetStorageRsyncTest extends \PHPUnit\Framework\TestCase
{
  public function testCompileCommand_upload__directoryStandardization()
  {
    // source and dest directories already have trailing slashes
    $rsyncer = new NetStorageRsync('host', '/directory', 'username', 'password');

    $actual = $rsyncer->compileCommand('/source/directory/', 'dest/directory/', ['file1.jpg', 'file2.gif']);
    $expected = 'rsync -a --include="file1.jpg" --include="file2.gif" --exclude="*" /source/directory/ username@host:/directory/dest/directory/ 2>&1';

    $this->assertEquals($expected, $actual);


    // automatically add trailing slashes to source and dest directories
    $rsyncer = new NetStorageRsync('host', '/directory', 'username', 'password');

    $actual = $rsyncer->compileCommand('/source/directory', 'dest/directory', ['file1.jpg', 'file2.gif']);
    $expected = 'rsync -a --include="file1.jpg" --include="file2.gif" --exclude="*" /source/directory/ username@host:/directory/dest/directory/ 2>&1';

    $this->assertEquals($expected, $actual);
  }

  public function testCompileCommand_upload__destinationSameAsRoot()
  {
    // source and dest directories already have trailing slashes
    $rsyncer = new NetStorageRsync('host', '/directory', 'username', 'password');

    $actual = $rsyncer->compileCommand('/source/directory/', '', ['file1.jpg', 'file2.gif']);
    $expected = 'rsync -a --include="file1.jpg" --include="file2.gif" --exclude="*" /source/directory/ username@host:/directory/ 2>&1';

    $this->assertEquals($expected, $actual);
  }

  public function testCompileCommand_upload__filenameEscaping()
  {
    $rsyncer = new NetStorageRsync('host', '/directory', 'username', 'password');

    // directories already have trailing slashes
    $actual = $rsyncer->compileCommand('/source/directory/', 'dest/directory/', ['file1\'s.jpg', 'file2"test".gif']);
    $expected = 'rsync -a --include="file1\'s.jpg" --include="file2\"test\".gif" --exclude="*" /source/directory/ username@host:/directory/dest/directory/ 2>&1';

    $this->assertEquals($expected, $actual);
  }

  public function testCompileCommand_upload__noHost()
  {
    $rsyncer = new NetStorageRsync('', '/directory', 'username', '');

    $actual = $rsyncer->compileCommand('/source/directory/', 'dest/directory/', ['file1.jpg', 'file2.gif']);
    $expected = 'rsync -a --include="file1.jpg" --include="file2.gif" --exclude="*" /source/directory/ /directory/dest/directory/ 2>&1';

    $this->assertEquals($expected, $actual);
  }

  public function testCompileCommand_upload__akamaiHost()
  {
    $rsyncer = new NetStorageRsync('upload.akamai.com', '12345', 'username', 'password');

    $actual = $rsyncer->compileCommand('/source/directory/', 'dest/directory/', ['file1.jpg', 'file2.gif']);
    $expected = 'rsync -a --include="file1.jpg" --include="file2.gif" --exclude="*" /source/directory/ username@upload.akamai.com::username/12345/dest/directory/ 2>&1';

    $this->assertEquals($expected, $actual);
  }

  public function testCompileCommand_upload__dryRun()
  {
    $rsyncer = new NetStorageRsync('', '/directory', 'username', '');

    $actual = $rsyncer->compileCommand('/source/directory/', 'dest/directory/', ['file1.jpg', 'file2.gif'], false, true);
    $expected = 'rsync -a --dry-run --verbose --include="file1.jpg" --include="file2.gif" --exclude="*" /source/directory/ /directory/dest/directory/ 2>&1';

    $this->assertEquals($expected, $actual);
  }

  public function testCompileCommand_upload__notAbsPathException()
  {
    $this->expectException(\InvalidArgumentException::class);
    $rsyncer = new NetStorageRsync('host', 'directory', 'username', 'password');
  }

  public function testCompileCommand_delete()
  {
    $rsyncer = new NetStorageRsync('host', '/directory', 'username', 'password');

    // directories already have trailing slashes
    $actual = $rsyncer->compileCommand('/source/directory/', 'dest/directory/', ['file1\'s.jpg', 'file2"test".gif'], true);
    $expected = 'rsync -a --delete --include="file1\'s.jpg" --include="file2\"test\".gif" --exclude="*" /source/directory/ username@host:/directory/dest/directory/ 2>&1';

    $this->assertEquals($expected, $actual);
  }
}
