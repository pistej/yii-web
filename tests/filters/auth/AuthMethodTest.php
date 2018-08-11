<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\filters\auth;

use yii\helpers\Yii;
use yii\base\Action;
use yii\web\filters\auth\AuthMethod;
use yii\web\Controller;
use yii\web\tests\filters\stubs\UserIdentity;
use yii\tests\TestCase;

class AuthMethodTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->mockWebApplication([
            'components' => [
                'user' => [
                    'identityClass' => UserIdentity::class,
                ],
            ],
        ]);
    }

    /**
     * Creates mock for [[AuthMethod]] filter.
     * @param callable $authenticateCallback callback, which result should [[authenticate()]] method return.
     * @return AuthMethod filter instance.
     */
    protected function createFilter($authenticateCallback)
    {
        $filter = $this->getMockBuilder(AuthMethod::class)
            ->setMethods(['authenticate'])
            ->getMock();
        $filter->method('authenticate')->willReturnCallback($authenticateCallback);

        return $filter;
    }

    /**
     * Creates test action.
     * @param array $config action configuration.
     * @return Action action instance.
     */
    protected function createAction(array $config = [])
    {
        $controller = new Controller('test', Yii::$app);
        return new Action('index', $controller, $config);
    }

    // Tests :

    public function testBeforeAction()
    {
        $action = $this->createAction();

        $filter = $this->createFilter(function () {return new \stdClass();});
        $this->assertTrue($filter->beforeAction($action));

        $filter = $this->createFilter(function () {return null;});
        $this->expectException('yii\web\UnauthorizedHttpException');
        $this->assertTrue($filter->beforeAction($action));
    }

    public function testIsOptional()
    {
        $reflection = new \ReflectionClass(AuthMethod::class);
        $method = $reflection->getMethod('isOptional');
        $method->setAccessible(true);

        $filter = $this->createFilter(function () {return new \stdClass();});

        $filter->optional = ['some'];
        $this->assertFalse($method->invokeArgs($filter, [$this->createAction(['id' => 'index'])]));
        $this->assertTrue($method->invokeArgs($filter, [$this->createAction(['id' => 'some'])]));

        $filter->optional = ['test/*'];
        $this->assertFalse($method->invokeArgs($filter, [$this->createAction(['id' => 'index'])]));
        $this->assertTrue($method->invokeArgs($filter, [$this->createAction(['id' => 'test/index'])]));
    }
}