<?php

class AlertBot
{
    private $token = '';
    private $api = 'https://api.telegram.org/bot';

    private $update;

    private $pdo;

    function __construct($update)
    {
        $this->update = $update;

        $this->api .= $this->token;

        $this->pdo = new PDO(
            'mysql:host=localhost;port=3306;dbname=ltservices_alertbot',
            'ltservices_alertbotuser',
            'WA62q,4^9r!x'
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function query($sql, $parameters, $return = false)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        if ($return === true)
            return $stmt->fetch(PDO::FETCH_ASSOC);
        else if ($return === 2)
            return $stmt->fetchAll();
        else
            return false;
    }

    public function checkCommands()
    {
        if (strpos($this->update['message']['text'], '/start') === 0) {
            $token = md5($this->update['message']['chat']['username'] . time() );
            try {
                $this->query(
                    'INSERT INTO users (tg_user_id, first_name, username, token, auth) VALUES (:tg_id, :fname, :uname, :token, :auth)',
                    [
                        ':tg_id' => $this->update['message']['chat']['id'],
                        ':fname' => $this->update['message']['chat']['first_name'],
                        ':uname' => $this->update['message']['chat']['username'],
                        ':token' => $token,
                        ':auth' => false
                    ]
                );
                $this->sendMessage(
                    $this->update['message']['chat']['id'],
                    "Добро пожаловать!\nДля того чтобы начать получать уведомления вам надо авторизироваться. Для получения токена обратитесь к создателю."
                );
            }
            catch (PDOException $exception) {
                $this->sendMessage(
                    $this->update['message']['chat']['id'],
                    "Вы уже начали диалог."
                );
            }

        }

        if (strpos($this->update['message']['text'], '/auth') === 0) {
            $result = $this->query(
              'SELECT auth, token FROM users WHERE tg_user_id = :tg_id',
              [':tg_id' => $this->update['message']['chat']['id']],
              true
            );

            if ($result === false) {
                $this->sendMessage(
                    $this->update['message']['chat']['id'],
                    'Зарегистрируйтесь командой /start'
                );
            }

            else if ( $result['auth'] == true ) {
                $this->sendMessage(
                    $this->update['message']['chat']['id'],
                    'Вы уже авторизированы.'
                );
            }

            else if ( strpos($this->update['message']['text'], $result['token']) !== false ) {
                $this->query(
                    'UPDATE users SET auth = :auth WHERE tg_user_id = :tg_id',
                    [
                        ':auth' => 1,
                        ':tg_id' => $this->update['message']['chat']['id']
                    ]
                );
                $this->sendMessage(
                    $this->update['message']['chat']['id'],
                    'Авторизация прошла успешно.'
                );
            }

            else {
                $this->sendMessage(
                    $this->update['message']['chat']['id'],
                    'Неверный токен авторизации.'
                );
            }
        }

        if (strpos($this->update['message']['text'], '/stop') === 0) {
            $result = $this->query(
                'SELECT auth FROM users WHERE tg_user_id = :tg_id',
                [':tg_id' => $this->update['message']['chat']['id']],
                true
            );

            if ($result['auth'] == 1) {
                $this->query(
                    'UPDATE users SET auth = :auth WHERE tg_user_id = :tg_id',
                    [
                        ':auth' => 0,
                        ':tg_id' => $this->update['message']['chat']['id']
                    ]
                );

                $this->sendMessage(
                    $this->update['message']['chat']['id'],
                    'Вы больше не будете получать уведомления. Чтобы получать их снова, авторизируйтесь еще раз.'
                );
            }
            else {
                $this->sendMessage(
                    $this->update['message']['chat']['id'],
                    'Вы не авторизированы, чтобы получать уведомления.'
                );
            }
        }
    }

    public function sendMessage($userId, $message)
    {
        $message = urlencode($message);
        file_get_contents($this->api . '/sendmessage?chat_id=' . $userId . '&text=' . $message . '&parse_mode=html');
    }

    public function sendMessageToAllAuthorizedUsers($message)
    {
        $result = $this->query(
            'SELECT user_id, tg_user_id FROM users WHERE auth = 1',
            [],
            2
        );

        if ($result) {
            foreach ($result as $user) {
                $this->sendMessage($user['tg_user_id'], $message);
            }
        }
    }

    public function isTypeOfBotCommand()
    {
        return strpos($this->update['message']['entities'][0]['type'], 'bot_command') === 0;
    }

}