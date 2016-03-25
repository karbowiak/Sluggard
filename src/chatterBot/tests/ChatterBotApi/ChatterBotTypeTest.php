<?php
/*
 * ChatterBotAPI Tests
 * Copyright (C) 2013 christiangaertner.film@googlemail.com
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use ChatterBotApi\ChatterBotType;

/**
* PHPUnit Test
*/
class ChatterBotTypeTest extends PHPUnit_Framework_TestCase
{
	public function testCleverbot()
	{
		$this->assertEquals(1, ChatterBotType::CLEVERBOT);
	}

	public function testJabberWacky()
	{
		$this->assertEquals(2, ChatterBotType::JABBERWACKY);
	}

	public function testPandoraBots()
	{
		$this->assertEquals(3, ChatterBotType::PANDORABOTS);
	}

	public function testPandoraBotsID()
	{
		$this->assertEquals('b0dafd24ee35a477', ChatterBotType::PANDORABOTS_DEFAULT_ID);
	}
}