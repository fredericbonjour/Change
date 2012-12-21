<?php

namespace ChangeTests\Change\Db\Query;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$app = \Change\Application::getInstance();
		$app->start();
		$instance = new \Change\Db\Query\Builder($app->getApplicationServices()->getDbProvider());
		$this->assertTrue(true);
	}
	
	public function testGetFromApplicationServices()
	{
		$app = \Change\Application::getInstance();
		$app->start();
		$qb = $app->getApplicationServices()->getQueryBuilder();
		$this->assertInstanceOf('\Change\Db\Query\Builder', $qb);
		return $qb;
	}
	
	/**
	 * @depends testGetFromApplicationServices
	 * @param \Change\Db\Query\Builder $qb
	 */
	public function testSelect(\Change\Db\Query\Builder $qb)
	{
		try 
		{
			$qb->query();
			$this->fail('A LogicException should be thrown.');
		}
		catch (\LogicException $e)
		{
			$this->assertTrue(true);
		}
		$qb->select('c1');
		$this->assertInstanceOf('\Change\Db\Query\SelectQuery', $qb->query());
		return $qb;
	}
	
	/**
	 * @depends testSelect
	 * @param \Change\Db\Query\Builder $qb
	 */
	public function testAddColumn(\Change\Db\Query\Builder $qb)
	{
		$fb = $qb->getFragmentBuilder();
		$qb->addColumn($fb->column('c2', 't1'));
		$this->assertEquals('SELECT "c1", "t1"."c2"', $qb->query()->toSQL92String());
		$qb->addColumn('c3');
		$this->assertEquals('SELECT "c1", "t1"."c2", "c3"', $qb->query()->toSQL92String());
	}
}