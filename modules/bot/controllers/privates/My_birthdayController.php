<?php

namespace app\modules\bot\controllers\privates;

use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use Yii;
use \app\modules\bot\components\response\SendMessageCommand;
use \app\modules\bot\components\response\AnswerCallbackQueryCommand;
use \app\modules\bot\components\response\EditMessageTextCommand;
use \app\models\User;
use app\modules\bot\components\Controller as Controller;

/**
 * Class My_birthdayController
 *
 * @package app\modules\bot\controllers
 */
class My_birthdayController extends Controller
{
    /**
     * @return array
     */
    public function actionIndex()
    {
        $telegramUser = $this->getTelegramUser();
        $user = $this->getUser();

        $birthday = $user->birthday;

        if (!isset($birthday)) {
            $telegramUser->getState()->setName('/set_birthday');
            $telegramUser->save();
        }

        return [
            new SendMessageCommand(
                $this->getTelegramChat()->chat_id,
                $this->render('index', [
                    'birthday' => isset($birthday)
                        ? (new \DateTime($birthday))->format(User::DATE_FORMAT)
                        : null,
                ]),
                [
                    'parseMode' => $this->textFormat,
                    'replyMarkup' => isset($birthday)
                        ? new InlineKeyboardMarkup([
                            [
                                [
                                    'callback_data' => '/change_birthday',
                                    'text' => Yii::t('bot', 'Change Birthday'),
                                ]
                            ]
                        ])
                        : null,
                ]
            ),
        ];
    }

    public function actionCreate()
    {
        $update = $this->getUpdate();
        $telegramUser = $this->getTelegramUser();
        $user = $this->getUser();

        $text = $update->getMessage()->getText();
        if ($this->validateDate($text, User::DATE_FORMAT)) {
            $user->birthday = \Yii::$app->formatter->format($text, 'date');
            $user->save();
            $telegramUser->getState()->setName(null);
            $telegramUser->save();
        }

        return $this->actionIndex();
    }

    public function actionUpdate()
    {
        $update = $this->getUpdate();
        $telegramUser = $this->getTelegramUser();

        $telegramUser->getState()->setName('/set_birthday');
        $telegramUser->save();

        return [
            new EditMessageTextCommand(
                $this->getTelegramChat()->chat_id,
                $update->getCallbackQuery()->getMessage()->getMessageId(),
                $update->getCallbackQuery()->getMessage()->getText(),
                [
                    'parseMode' => $this->textFormat,
                ]
            ),
            new SendMessageCommand(
                $this->getTelegramChat()->chat_id,
                $this->render('update'),
                [
                    'parseMode' => $this->textFormat,
                ]
            ),
            new AnswerCallbackQueryCommand(
                $update->getCallbackQuery()->getId()
            ),
        ];
    }

    private function validateDate($date, $format)
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
