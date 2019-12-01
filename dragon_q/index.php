<?php
    ini_set('log_errors','on');
    ini_set('error_log','php.log');

    session_start();

    $monsters = array();

    class Sex{
        const MAN = 1;
        const WOMAN = 2;
        const DOG = 3;
    }

    abstract class Creature{
        protected $name;
        protected $hp;
        protected $attackMin;
        protected $attackMax;
        abstract function sayCry();
        public function setName($str){
            $this->name = $str;
        }
        public function getName(){
            return $this->name;
        }
        public function setHp($num){
            $this->hp = $num;
        }
        public function getHp(){
            return $this->hp;
        }
        public function attack($targetObj){
            $attackPoint = mt_rand($this->attackMin, $this->attackMax);
            if(!mt_rand(0,4)){
                $attackPoint = $attackPoint * 1.5;
                $attackPoint = (int)$attackPoint;
                History::set($this->getName().'のクリティカルヒット');
            }
            $targetObj->setHp($targetObj->getHp() - $attackPoint);
            History::set($attackPoint.'ポイントダメージ');
        }
    }

    class Human extends Creature {
        protected $sex;
        public function __construct($name, $sex, $hp, $attackMin, $attackMax){
            $this->name = $name;
            $this->sex = $sex;
            $this->hp = $hp;
            $this->attackMin = $attackMin;
            $this->attackMax = $attackMin;
        }
        public function setSex($num){
            $this->sex = $num;
        }
        public function getSex(){
            return $this->sex;
        }
        public function sayCry(){
            History::set($this->name.'が叫ぶ');
            switch($this->sex){
                case 1:
                    History::set('ぐはぁ');
                break;

                case 2:
                    History::set('きゃー');
                break;

                case 3:
                    History::set('ワンワン');
                break;
            }
        }
    }

    class Monster extends Creature{
        protected $img;
        public function __construct($name, $hp, $img, $attackMin, $attackMax){
            $this->name = $name;
            $this->hp = $hp;
            $this->img = $img;
            $this->attackMin = $attackMin;
            $this->attackMax = $attackMax;
        }
        public function getImg(){
            return $this->img;
        }
        public function sayCry(){
            History::set($this->name.'が叫ぶ');
            History::set('ギャース');
        }
    }

    class MagicMonster extends Monster{
        private $magicAttack;
        public function __construct($name, $hp, $img, $attackMin, $attackMax){
            parent::__construct($name, $hp, $img, $attackMin, $attackMax);
            $this->magicAttack = $magicAttack;
        }
        public function getMagicAttack(){
            return $this->magicAttack;
        }
        public function attack($targetObj){
            if(!mt_rand(0,4)){
                History::set($this->name.'の魔法攻撃');
                $targetObj->setHp($targetObj->getHp() - $this->magicAttack);
                History::set($this->magicAttack.'ポイントダメージを受けた');
            }else{
                parent::attack($targetObj);
            }
        }
    }

    interface HistoryInterface{
        public function set($str);
        public function clear();
    }

    class History implements HistoryInterface{
        public function set($str){
            if(empty($_SESSION['history'])) $_SESSION['history'] = '';
            $_SESSION['history'] .= $str.'<br>';
        }
        public function clear(){
        unset($_SESSION['history']);
        }
    }

    $human = new Human('勇者', mt_rand(1,3), 500, 40, 120);
    $monsters[] = new Monster( 'グリーン1つ目', 100, 'img/monster01.png', 20, 40 );
    $monsters[] = new MagicMonster( '怒りの針頭', 300, 'img/monster02.png', 20, 60, mt_rand(50, 100) );
    $monsters[] = new Monster( '多めだまん', 200, 'img/monster03.png', 30, 50 );
    $monsters[] = new MagicMonster( '大食い男爵', 400, 'img/monster04.png', 50, 80, mt_rand(60, 120) );
    $monsters[] = new Monster( 'ギョロ目', 150, 'img/monster05.png', 30, 60 );
    $monsters[] = new Monster( 'スライミー', 100, 'img/monster06.png', 10, 30 );
    $monsters[] = new Monster( 'グリーン2つ目', 120, 'img/monster07.png', 20, 30 );
    $monsters[] = new Monster( '黒い人型', 180, 'img/monster08.png', 30, 50 );

    function createMonster(){
        global $monsters;
        $monster = $monsters[mt_rand(0,7)];
        History::set($monster->getName().'が現れた');
        $_SESSION['monster'] = $monster;
    }
    function createHuman(){
        global $human;
        $_SESSION['human'] = $human;
    }
    function init(){
        History::clear();
        History::set('初期化します');
        $_SESSION['countDownCount'] = 0;
        createHuman();
        createMonster();
    }
    function gameOver(){
        $_SESSION = array();
    }

    if(!empty($_POST)){
        $attackFlg = (!empty($_POST['attack'])) ? true :false;
        $startFlg = (!empty($_POST['start'])) ? true :false;
        error_log('POSTあり');

        if($startFlg){
            History::set('ゲームを始めます');
            init();
        }else{
            if($attackFlg){
                History::set($_SESSION['human']->getName().'の攻撃');
                $_SESSION['human']->attack($_SESSION['monster']);
                $_SESSION['monster']->sayCry();

                History::set($_SESSION['monster']->getName().'の攻撃');
                $_SESSION['monster']->attack($_SESSION['human']);
                $_SESSION['human']->sayCry();

                if($_SESSION['human']->getHp() <= 0){
                    gameOver();
                }else{
                    if($_SESSION['monster']->getHp() <= 0){
                        History::set($_SESSION['monster']->getName().'を倒した');
                        createMonster();
                        $_SESSION['knockDownCount'] = $_SESSION['history'] + 1;
                    }
                }
            }else{
                History::set('逃げた');
                History::set('この腰抜け！');
                createMonster();
            }
        }
        $_POST = array();
    }
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>ドラゴンQ</title>
        <link rel="stylesheet" type="text/css" href="./css/style.css">
    </head>
    <body>
        <h1 class="game-title">ゲーム | ドラゴンQ</h1>
        <div class="box">
            <?php
                if(empty($_SESSION)){
            ?>
            <h2 class="fast-text">GAME START</h2>
            <form action="" method="post" class="form-1">
                <input type="submit" name="start" value="Game start" class="game-start-btn">
            </form>
            <?php
                }else{
            ?>
            <h2><?php echo $_SESSION['monster']->getName().'が現れた' ?></h2>
            <div class="img-box">
                <img src="<?php echo $_SESSION['monster']->getImg(); ?>" class="monster-img">
            </div>
            <p class="center-text">モンスターHP：<?php echo $_SESSION['monster']->getHP(); ?></p>
            <p class="center-text">倒したモンスター数：<? echo $_SESSION['knockDownCount']; ?></p>
            <p class="center-text">勇者HP：<?php echo $_SESSION['human']->getHp(); ?>/500</p>
            <form action="" method="post" class="form-2">
                <input type="submit" name="attack" value="攻撃" >
                <input type="submit" name="escape" value="戦略的撤退" >
                <input type="submit" name="start" value="ゲームリスタート" >
            </form>
            <?php
                }
            ?>
            <div class="history-box">
                <p class="scroll-area"><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
            </div>
        </div>
    </body>
</html>