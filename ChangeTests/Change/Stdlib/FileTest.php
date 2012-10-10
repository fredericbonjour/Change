<?php
namespace ChangeTests\Change\Stdlib;

class FileTest extends \PHPUnit_Framework_TestCase
{
	public function testMkdir()
	{		
		$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'FileTest' . DIRECTORY_SEPARATOR . __METHOD__ . DIRECTORY_SEPARATOR .  'Test';
		// Cleanup dir
		$components = explode(DIRECTORY_SEPARATOR, $path);
		@rmdir(implode(DIRECTORY_SEPARATOR, $components));
		array_pop($components);
		@rmdir(implode(DIRECTORY_SEPARATOR, $components));
		array_pop($components);
		@rmdir(implode(DIRECTORY_SEPARATOR, $components));
		\Change\Stdlib\File::mkdir($path);
		$this->assertFileExists($path);
		$this->setExpectedException('\RuntimeException', 'Could not create directory');
		\Change\Stdlib\File::mkdir(__FILE__);
	}
	
	public function testWrite()
	{
		$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'FileTest' . DIRECTORY_SEPARATOR .  'testWrite' . DIRECTORY_SEPARATOR .  'test.txt';
		@unlink($path);
		\Change\Stdlib\File::write($path, 'test');
		$this->assertFileExists($path);
		$content = file_get_contents($path);
		$this->assertEquals('test', $content);
		$this->setExpectedException('\RuntimeException', 'Could not write file');
		\Change\Stdlib\File::write(__DIR__, 'test');
		return $path;
	}
	
	/**
	 * @depends testWrite
	 */
	public function testRead($path)
	{
		$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'FileTest' . DIRECTORY_SEPARATOR .  'testWrite' . DIRECTORY_SEPARATOR .  'test.txt';
		$this->assertEquals('test', \Change\Stdlib\File::read($path));
		$this->setExpectedException('\RuntimeException', 'Could not read');
		\Change\Stdlib\File::read(__DIR__ . DIRECTORY_SEPARATOR . 'aazeazeazeazeazeaze');
	}
}