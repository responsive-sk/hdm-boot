<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session\Tests;

use PHPUnit\Framework\TestCase;
use ResponsiveSk\Slim4Session\SessionManager;
use ResponsiveSk\Slim4Session\Exceptions\SessionException;

/**
 * Unit tests for SessionManager.
 */
class SessionManagerTest extends TestCase
{
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        $this->sessionManager = new SessionManager();
    }

    protected function tearDown(): void
    {
        // Clean up session if started
        if ($this->sessionManager->isStarted()) {
            $this->sessionManager->destroy();
        }
    }

    public function testIsStartedReturnsFalseInitially(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
    }

    public function testStartSession(): void
    {
        $result = $this->sessionManager->start();
        $this->assertTrue($result);
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testStartSessionTwiceReturnsTrueWithoutError(): void
    {
        $this->sessionManager->start();
        $result = $this->sessionManager->start();
        $this->assertTrue($result);
    }

    public function testSetAndGetValue(): void
    {
        $this->sessionManager->start();
        $this->sessionManager->set('test_key', 'test_value');
        
        $this->assertEquals('test_value', $this->sessionManager->get('test_key'));
    }

    public function testGetWithDefault(): void
    {
        $this->sessionManager->start();
        
        $this->assertEquals('default', $this->sessionManager->get('nonexistent', 'default'));
    }

    public function testHasKey(): void
    {
        $this->sessionManager->start();
        $this->sessionManager->set('existing_key', 'value');
        
        $this->assertTrue($this->sessionManager->has('existing_key'));
        $this->assertFalse($this->sessionManager->has('nonexistent_key'));
    }

    public function testRemoveKey(): void
    {
        $this->sessionManager->start();
        $this->sessionManager->set('key_to_remove', 'value');
        
        $this->assertTrue($this->sessionManager->has('key_to_remove'));
        
        $this->sessionManager->remove('key_to_remove');
        
        $this->assertFalse($this->sessionManager->has('key_to_remove'));
    }

    public function testClearSession(): void
    {
        $this->sessionManager->start();
        $this->sessionManager->set('key1', 'value1');
        $this->sessionManager->set('key2', 'value2');
        
        $this->sessionManager->clear();
        
        $this->assertFalse($this->sessionManager->has('key1'));
        $this->assertFalse($this->sessionManager->has('key2'));
    }

    public function testGetAllData(): void
    {
        $this->sessionManager->start();
        $this->sessionManager->set('key1', 'value1');
        $this->sessionManager->set('key2', 'value2');
        
        $data = $this->sessionManager->all();
        
        $this->assertArrayHasKey('key1', $data);
        $this->assertArrayHasKey('key2', $data);
        $this->assertEquals('value1', $data['key1']);
        $this->assertEquals('value2', $data['key2']);
    }

    public function testRegenerateId(): void
    {
        $this->sessionManager->start();
        $oldId = $this->sessionManager->getId();
        
        $result = $this->sessionManager->regenerateId();
        $newId = $this->sessionManager->getId();
        
        $this->assertTrue($result);
        $this->assertNotEquals($oldId, $newId);
    }

    public function testFlashInterface(): void
    {
        $this->sessionManager->start();
        
        $flash = $this->sessionManager->flash();
        $this->assertInstanceOf(\ResponsiveSk\Slim4Session\FlashInterface::class, $flash);
        
        $flash->add('success', 'Test message');
        $this->assertTrue($flash->has('success'));
        
        $messages = $flash->get('success');
        $this->assertContains('Test message', $messages);
    }

    public function testSetNameBeforeStart(): void
    {
        $this->sessionManager->setName('custom_session');
        $this->sessionManager->start();
        
        $this->assertEquals('custom_session', $this->sessionManager->getName());
    }

    public function testSetNameAfterStartThrowsException(): void
    {
        $this->sessionManager->start();
        
        $this->expectException(SessionException::class);
        $this->sessionManager->setName('new_name');
    }

    public function testDestroySession(): void
    {
        $this->sessionManager->start();
        $this->sessionManager->set('test', 'value');
        
        $result = $this->sessionManager->destroy();
        
        $this->assertTrue($result);
        $this->assertFalse($this->sessionManager->isStarted());
    }
}
