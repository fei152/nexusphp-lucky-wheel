# v1.0.1

NexusPHP 幸运大转盘插件首个稳定发布版。

## 主要功能

- 后台“幸运大转盘”统一入口，包含“设置 / 奖品 / 记录”三个标签页。
- 支持配置抽奖开关、每次消耗、每日次数、最低用户等级。
- 支持奖品权重、库存、排序、启用状态和前台描述。
- 支持奖品权重显示开关，默认不向用户展示权重。
- 支持中奖记录入库。
- 支持虚拟奖品自动发放。
- 支持人工/实物奖品，中奖后自动给管理员发送站内通知。
- 支持简体中文、繁体中文、英文。

## 支持奖品

- 魔力值
- 上传量
- 邀请码
- 去广告时长
- 彩虹 ID 时长
- 勋章
- VIP 时长
- 人工发放奖品
- 实物奖品

## 前台入口

- 未安装自定义菜单插件时，自动在 NexusPHP 默认导航栏添加入口。
- 已安装自定义菜单插件时，自动创建或启用 `luckywheel.php` 菜单项。
- 关闭大转盘后，前台入口自动隐藏或禁用。

## 安装

```bash
cd /path/to/nexusphp
mkdir -p packages
cp -R /path/to/nexusphp-lucky-wheel packages/nexus-lucky-wheel

COMPOSER_ALLOW_SUPERUSER=1 composer config repositories.lucky-wheel path packages/nexus-lucky-wheel
COMPOSER_ALLOW_SUPERUSER=1 composer require xiaomlove/nexusphp-lucky-wheel:*

php artisan plugin install xiaomlove/nexusphp-lucky-wheel
php artisan optimize:clear
```

建议安装后检查：

```bash
php -l include/functions.php
php -l public/luckywheel.php
php -l public/takeluckywheel.php
```

## 兼容性

- NexusPHP `v1.10.2`
- PHP `>=8.2 <8.6`
