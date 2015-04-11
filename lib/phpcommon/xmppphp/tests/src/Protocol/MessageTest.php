<?php

/**
 * Copyright 2014 Fabian Grutschus. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are those
 * of the authors and should not be interpreted as representing official policies,
 * either expressed or implied, of the copyright holders.
 *
 * @author    Fabian Grutschus <f.grutschus@lubyte.de>
 * @copyright 2014 Fabian Grutschus. All rights reserved.
 * @license   BSD
 * @link      http://github.com/fabiang/xmpp
 */

namespace Fabiang\Xmpp\Protocol;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-01-20 at 13:25:49.
 *
 * @coversDefaultClass Fabiang\Xmpp\Protocol\Message
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Message
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->object = new Message;
    }

    /**
     * Test turning object into string.
     *
     * @covers ::toString
     * @uses Fabiang\Xmpp\Protocol\Message::__construct
     * @uses Fabiang\Xmpp\Protocol\Message::getType
     * @uses Fabiang\Xmpp\Protocol\Message::setType
     * @uses Fabiang\Xmpp\Protocol\Message::getTo
     * @uses Fabiang\Xmpp\Protocol\Message::setTo
     * @uses Fabiang\Xmpp\Protocol\Message::getMessage
     * @uses Fabiang\Xmpp\Protocol\Message::setMessage
     * @uses Fabiang\Xmpp\Util\XML::generateId
     * @uses Fabiang\Xmpp\Util\XML::quote
     * @uses Fabiang\Xmpp\Util\XML::quoteMessage
     * @return void
     */
    public function testToString()
    {
        $this->object->setTo('foobar')->setMessage('testmessage');
        $this->assertRegExp(
            '#<message type="chat" id="fabiang_xmpp_[^"]+" to="foobar"><body>testmessage</body></message>#',
            $this->object->toString()
        );
    }

    /**
     * Test constructor.
     *
     * @covers ::__construct
     * @uses Fabiang\Xmpp\Protocol\Message::getType
     * @uses Fabiang\Xmpp\Protocol\Message::setType
     * @uses Fabiang\Xmpp\Protocol\Message::getTo
     * @uses Fabiang\Xmpp\Protocol\Message::setTo
     * @uses Fabiang\Xmpp\Protocol\Message::getMessage
     * @uses Fabiang\Xmpp\Protocol\Message::setMessage
     * @return void
     */
    public function testConstructor()
    {
        $object = new Message('foobar', 'to', 'groupchat');
        $this->assertSame('groupchat', $object->getType());
        $this->assertSame('to', $object->getTo());
        $this->assertSame('foobar', $object->getMessage());
    }

    /**
     * Test setters and getters.
     *
     * @covers ::getType
     * @covers ::setType
     * @covers ::getTo
     * @covers ::setTo
     * @covers ::getMessage
     * @covers ::setMessage
     * @uses Fabiang\Xmpp\Protocol\Message::__construct
     * @return void
     */
    public function testSettersAndGetters()
    {
        $this->assertSame('chat', $this->object->getType());
        $this->assertSame('groupchat', $this->object->setType('groupchat')->getType());
        $this->assertSame('to', $this->object->setTo('to')->getTo());
        $this->assertSame('foobar', $this->object->setMessage('foobar')->getMessage());
    }

}