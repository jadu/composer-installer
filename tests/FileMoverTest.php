<?php
use Jadu\Composer\FileMover;
use \Mockery as m;

class FileMoverTest extends PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();

        if (file_exists('tests/test_source/bar.txt')) {
            unlink('tests/test_source/bar.txt');
            rmdir('tests/test_source');
        }

        if (file_exists('tests/test_dest/bar.txt')) {
            unlink('tests/test_dest/bar.txt');
            rmdir('tests/test_dest');
        }
    }

    protected function createSource()
    {
        mkdir('tests/test_source');
        file_put_contents('tests/test_source/bar.txt', 'test');
    }

    protected function createDest()
    {
        mkdir('tests/test_dest');
        file_put_contents('tests/test_dest/bar.txt', 'test');
    }

    /**
     * @return array
     */
    protected function mockDepedencies()
    {
        //  Mock for $installer
        $libraryInstaller = m::mock('Composer\Installer\LibraryInstaller');

        $package = m::mock('Composer\Package\PackageInterface');
        $io = m::mock('Composer\IO\IOInterface');
        $installer = m::mock('Jadu\Composer\Installer');
        return array(
            $package,
            $io,
            $installer
        );
    }

    /**
     *  Check if class exists
     */
    public function testClassIsInstanceOf()
    {
        list($package, $io, $installer) = $this->mockDepedencies();

        $class = new FileMover($package, $io, $installer);

        $this->assertTrue($class instanceof \Jadu\Composer\FileMover);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCopyFileThrowsErrorIfSourceFileDoesntExist()
    {
        list($package, $io, $installer) = $this->mockDepedencies();
        $installer->shouldReceive('getPackageBasePath')->once()->andReturn('');
        $installer->shouldReceive('getRootPath')->once()->andReturn('');

        $class = new FileMover($package, $io, $installer);

        $this->assertTrue($class->copyFile('foo/bar.txt', 'other/dir/foo/bar'));
    }

    public function testCopyFileThrowsErrorInIOIfFileExistsAndIsNotOverriden()
    {
        $this->createSource();
        $this->createDest();

        list($package, $io, $installer) = $this->mockDepedencies();
        $installer->shouldReceive('getPackageBasePath')->once()->andReturn(getcwd() . '/tests');
        $installer->shouldReceive('getRootPath')->once()->andReturn(getcwd() . '/tests');

        $io->shouldReceive('writeError')->once();

        $class = new FileMover($package, $io, $installer);

        $this->assertNull($class->copyFile('test_source', 'test_dest', false));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCopyFileCreatesFolderStructureIfItDoesntExistButFailsToCreateIt()
    {
        $this->createSource();

        list($package, $io, $installer) = $this->mockDepedencies();
        $installer->shouldReceive('getPackageBasePath')->once()->andReturn('tests');
        $installer->shouldReceive('getRootPath')->once()->andReturn( 'tests');

        //  Create partial mock to check if createFolder is actually called if the orig directory doesn't exist
        $mockFileMover = m::mock('Jadu\Composer\FileMover[createFolder]', array($package, $io, $installer));
        $mockFileMover->shouldReceive('createFolder')->with('tests/test_dest')->andReturn(false);

        $mockFileMover->copyFile('test_source/bar.txt', 'test_dest/bar.txt',true,false,false);
    }

    public function testCopyFileCreatesFolderStructureIfItDoesntExist()
    {
        $this->createSource();

        list($package, $io, $installer) = $this->mockDepedencies();
        $installer->shouldReceive('getPackageBasePath')->once()->andReturn('tests');
        $installer->shouldReceive('getRootPath')->once()->andReturn( 'tests');

        $class = new FileMover($package, $io, $installer);

        $this->assertTrue($class->copyFile('test_source/bar.txt', 'test_dest/bar.txt',true,false,false));
    }
}
