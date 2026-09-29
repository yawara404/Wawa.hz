let scene = 'title';
let player, ground;
let playerImg, monsterImg;
let overlaySprite, overlayE;

let baseColor = '#F4F1EA';
let mainColor = '#6B5E51';
let subColor = '#8A9A86';
let accentColor = '#D38C7D';

let enemies = [];

let startFrame = 0;
let surviveTime = 0;

function preload() {
    playerImg = loadImage('./images/character_monster_slime_green.png');
    monsterImg = loadImage('./images/yurei_01.png');
}

function setup() {
    new Canvas(640, 480);
    world.gravity.y = 10;
}

function initTitle() {
    allSprites.removeAll();
}

function drawTitle() {
    background(baseColor);

    textSize(40);
    textStyle(BOLD);
    textAlign(CENTER);
    fill(subColor);
    text('Falling Survivor', width / 2, height / 2 - 40);

    textSize(20);
    textStyle(NORMAL);
    fill(mainColor);
    text('↑キー: ジャンプ / 左右キー: 移動', width / 2, height / 2 + 20);

    textSize(20);
    textStyle(BOLD);
    textAlign(CENTER);
    fill(mainColor);
    text('スペースキーでスタート！', width / 2, height / 2 + 80);

    textSize(20);
    textStyle(BOLD);
    textAlign(CENTER);
    fill(accentColor);
    text('Get highRank! : B ~ SSS', width / 2, height / 2 - 100);


    if (kb.presses('space')) {
        initGame();
        scene = 'game';
    }
}

function newPlayer() {
    player = new Sprite(320, 240, 40, 40)
    player.color = subColor;
    playerImg.resize(45, 35);
    player.textSize = 30;
    player.rotationLock = true;
}


function initGame() {
    allSprites.removeAll();
    startFrame = frameCount;
    enemies = [];

    ground = new Sprite(320, 480, 640, 50, 'static');
    ground.color = baseColor;
    newPlayer();

    player.image = playerImg;

    // overlaySprite = new Sprite(320, 240);
    // overlaySprite.image = playerImg;
    // overlaySprite.collider = 'none';
    // overlaySprite.layer = player.layer + 1;

    monsterImg.resize(50, 50);
}

// 画面右外から敵を1体出現させる
// 生成したスプライトは必ず enemies に登録する（登録漏れ＝削除されないスプライトを作らない）
function spawnEnemy(speedMin, speedMax) {
    let randomY = random(height / 2, height - 60);
    // 画像は50x50で描画されるため、画像全体が画面外になる位置から出す
    let e = new Sprite(width + 25, randomY, 40, 40, 'kinematic');
    e.color = accentColor;
    e.image = monsterImg;
    e.vel.x = random(speedMin, speedMax);
    enemies.push(e);
    return e;
}

function drawGame() {
    background('#fff');

    let currentSec = floor((frameCount - startFrame) / 60);

    movePlayer();

    // 100秒以降は出現頻度と速度を上げ、同時出現もしやすくする。
    let spawnChance = currentSec >= 100 ? 4.5 : 3;
    if (random(0, 100) < spawnChance) {
        if (currentSec >= 100) {
            spawnEnemy(-18, -22);
            if (random(0, 100) < 25) {
                spawnEnemy(-18, -22);
            }
        } else if (currentSec > 20) {
            spawnEnemy(-14, -18);
            if (random(0, 100) < 10) {
                spawnEnemy(-14, -18);
            }
        } else {
            spawnEnemy(-8, -10);
        }
    }

    for (let i = enemies.length - 1; i >= 0; i--) {
        let e = enemies[i];
        // 画面左外に出た敵、または画面右外で止まってしまった敵を削除
        if (e.x < -20 || (e.x > width && e.vel.x >= 0)) {
            e.remove();
            enemies.splice(i, 1);
        }
    }

    // overlaySprite.x = player.x;
    // overlaySprite.y = player.y;

    textSize(20);
    textStyle(NORMAL);
    textAlign(RIGHT);
    fill('black');
    text('TIME: ' + zeroPadding(currentSec), 630, 40);
    text('RANK: ' + getRank(currentSec), 630, 80);

    textSize(20);
    textAlign(LEFT);
    fill('black');
    text('Clear: ↑20sec', 10, 40);
    text('State: ' + getClear(currentSec), 10, 80);

    if (player.y > 480) {
        surviveTime = currentSec;
        if (currentSec < 20) {
            initGameOver();
            scene = 'gameover';

        } else {
            initResult();
            scene = 'result';
        }
    }
}

function initResult() {
    allSprites.removeAll();
}

function drawResult() {
    background(baseColor);

    textSize(30);
    textStyle(BOLD);
    textAlign(CENTER);
    fill(accentColor);
    text('CLEAR TIME: ' + surviveTime + ' 秒', width / 2, height / 2 - 40);


    textSize(30);
    textAlign(CENTER);
    fill(accentColor);
    text('RANK: ' + getRank(surviveTime), width / 2, height / 2);


    textSize(16);
    fill(subColor);
    text('スペースキーでタイトルへ戻る', width / 2, height / 2 + 40);

    if (kb.presses('space')) {
        initTitle();
        scene = 'title';
    }
}

function initGameOver() {
    allSprites.removeAll();
}

function drawGameOver() {
    background(baseColor);

    textSize(50);
    textStyle(NORMAL);
    textAlign(CENTER);
    fill(accentColor);
    text('GAME OVER...', width / 2, height / 2 - 40);

    textSize(30);
    fill(mainColor);
    text('生存記録: ' + surviveTime + '秒', width / 2, height / 2 + 20);

    textSize(20);
    textStyle(BOLD);
    fill(subColor);
    text('スペースキーでタイトルへ', width / 2, height / 2 + 100);

    if (kb.presses('space')) {
        initTitle();
        scene = 'title';
    }
}

function draw() {
    if (scene == 'title') {
        drawTitle();
    } else if (scene == 'game') {
        drawGame();
    } else if (scene == 'result') {
        drawResult();
    } else if (scene == 'gameover') {
        drawGameOver();
    }
}

function movePlayer() {
    if (kb.pressing('right')) { player.x += 10; player.vel.x = 0; }
    if (kb.pressing('left')) { player.x -= 10; player.vel.x = 0; }
    if (kb.pressing('up') && (player.colliding(ground) || (enemies[0] && player.colliding(enemies[0])))) {
        player.vel.y -= 5;
    }
    if (kb.pressing('down') && player.y < height) {
        player.vel.y += 3;
    }
}

function getRank(s) {
    if (s >= 200) {
        return 'LR (幻)'
    } else if (s >= 100) {
        return 'SSS (伝説)'
    } else if (s >= 50) {
        return 'SS (名人)'
    } else if (s >= 30) {
        return 'S (達人)';
    } else if (s >= 20) {
        return 'A (一人前)';
    } else {
        return 'B (初心者)';
    }
}

function getClear(s) {
    if (s >= 20) {
        return 'Clear';
    } else {
        return 'Fail';
    }
}

function zeroPadding(num) {
    if (num < 10) {
        return '0' + num;
    } else {
        return num;
    }
}
