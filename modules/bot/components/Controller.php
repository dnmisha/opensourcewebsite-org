<?php

namespace app\modules\bot\components;

use yii\base\InlineAction;
use yii\base\InvalidCallException;
use yii\web\BadRequestHttpException;

/**
 * Class CommandController
 *
 * @package app\modules\bot
 */
class Controller extends \yii\base\Controller
{
    protected $textFormat = 'html';

    /**
     * @var bool
     */
    public $layout = false;

    /**
     * @var array the parameters bound to the current action.
     */
    public $actionParams = [];

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[\yii\base\Action]] when it begins to run with the given parameters.
     * This method will check the parameter names that the action requires and return
     * the provided parameters according to the requirement. If there is any missing parameter,
     * an exception will be thrown.
     * @param \yii\base\Action $action the action to be bound with parameters
     * @param array $params the parameters to be bound to the action
     * @return array the valid parameters that the action can run with.
     * @throws BadRequestHttpException if there are missing or invalid parameters.
     * @throws \ReflectionException
     */
    public function bindActionParams($action, $params)
    {
        if ($action instanceof InlineAction) {
            $method = new \ReflectionMethod($this, $action->actionMethod);
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }

        $args = [];
        $missing = [];
        $actionParams = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                if ($param->isArray()) {
                    $args[] = $actionParams[$name] = (array) $params[$name];
                } elseif (!is_array($params[$name])) {
                    $args[] = $actionParams[$name] = $params[$name];
                } else {
                    throw new BadRequestHttpException(\Yii::t('yii', 'Invalid data received for parameter "{param}".', [
                        'param' => $name,
                    ]));
                }
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $actionParams[$name] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }

        if (!empty($missing)) {
            throw new BadRequestHttpException(\Yii::t('yii', 'Missing required parameters: {params}', [
                'params' => implode(', ', $missing),
            ]));
        }

        $this->actionParams = $actionParams;

        return $args;
    }

    public function render($view, $params = [])
    {
        return $this->prepareText(parent::render($view, $params));
    }

    protected function getTelegramUser()
    {
        return $this->module->telegramUser;
    }

    protected function getTelegramChat()
    {
        return $this->module->telegramChat;
    }

    protected function getUser()
    {
        return $this->module->user;
    }

    protected function getUpdate()
    {
        return $this->module->update;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function prepareText($text)
    {
        if ($this->textFormat == 'html')
        {
            $text = str_replace(["\n", "\r\n"], '', $text);
            $text = preg_replace('/<br\W*?\/>/i', PHP_EOL, $text);
        }
        return $text;
    }
}
