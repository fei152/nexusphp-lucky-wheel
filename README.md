# NexusPHP 幸运大转盘插件

这是一个适用于 NexusPHP `v1.10.2` 的幸运大转盘插件。后台可以设置抽奖开关、每次抽奖消耗、每日次数限制、最低用户等级、奖品权重、库存、奖品描述，以及是否在前台显示中奖权重。

插件支持虚拟奖品自动发放，也支持实物或其它需要人工处理的奖品。用户抽中人工奖品后，系统会自动给管理员发送站内通知，方便后续发奖。

## 功能介绍

- 后台只有一个“幸运大转盘”入口，进入后有“设置 / 奖品 / 记录”三个标签页。
- 奖品支持权重、库存、排序、启用状态、奖品描述。
- 支持前台隐藏或显示奖品权重，默认不显示，避免用户看到真实概率。
- 支持中奖记录入库，前台和后台都可以查看。
- 支持虚拟奖品自动发放。
- 支持实物奖品、人工奖品，中奖后自动通知管理员。
- 支持简体中文、繁体中文、英文。
- 支持没有自定义菜单插件时自动添加前台入口。
- 支持已安装自定义菜单插件时自动创建或启用大转盘菜单项。

## 支持的奖品类型

- 魔力值
- 上传量，单位 GiB
- 邀请码
- 去广告时长
- 彩虹 ID 时长
- 勋章
- VIP 时长
- 人工发放奖品
- 实物奖品

部分数值型奖品支持填写负数。例如魔力值奖品填写 `-200`，用户中奖后会扣除 200 魔力值。

## 环境要求

- NexusPHP `v1.10.2`
- PHP `>=8.2 <8.6`
- Composer

## 安装方式

把插件源码放到 NexusPHP 项目的 `packages` 目录：

```bash
cd /path/to/nexusphp
mkdir -p packages
cp -R /path/to/nexusphp-lucky-wheel packages/nexus-lucky-wheel
```

注册本地 Composer 包并安装：

```bash
COMPOSER_ALLOW_SUPERUSER=1 composer config repositories.lucky-wheel path packages/nexus-lucky-wheel
COMPOSER_ALLOW_SUPERUSER=1 composer require xiaomlove/nexusphp-lucky-wheel:*
```

执行 NexusPHP 插件安装命令：

```bash
php artisan plugin install xiaomlove/nexusphp-lucky-wheel
php artisan optimize:clear
```

确认运行目录权限：

```bash
chown -R www-data:www-data storage bootstrap/cache
```

## 安装后检查

建议安装后执行：

```bash
php -l include/functions.php
php -l public/luckywheel.php
php -l public/takeluckywheel.php
```

如果都显示 `No syntax errors detected`，说明入口文件语法正常。

前台访问地址：

```text
https://你的站点/luckywheel.php
```

后台进入：

```text
管理后台 -> 幸运大转盘
```

## 前台入口逻辑

如果没有安装自定义菜单插件，幸运大转盘会自动在 NexusPHP 默认导航栏里添加入口。

如果安装了自定义菜单插件，幸运大转盘会自动创建或启用 `luckywheel.php` 菜单项，不会覆盖其它自定义菜单内容。

关闭幸运大转盘后，前台入口会自动隐藏或禁用。

## 奖品权重说明

奖品权重是相对概率，不是百分比。

例如三个奖品权重分别是：

```text
100
50
1
```

表示抽中概率按 `100:50:1` 的比例计算。

前台默认不显示权重。如果希望用户看到，可以在后台设置里打开“显示奖品权重”。

## 备份说明

插件安装时会发布部分后台页面、前台入口文件和语言文件。如果目标文件已存在且内容不同，会自动生成 `.lucky-wheel.bak` 备份文件。

## 许可证

GPL-2.0-only
