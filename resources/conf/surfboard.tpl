#!MANAGED-CONFIG {$userapiUrl}

#---------------------------------------------------#
## 更新：{date("Y-m-d h:i:s")}
#---------------------------------------------------#

[General]
loglevel = notify
dns-server = system, 119.29.29.29, 223.6.6.6, 80.80.80.80
skip-proxy = 127.0.0.1, 192.168.0.0/16, 10.0.0.0/8, 172.16.0.0/12, 100.64.0.0/10, 17.0.0.0/8, localhost, *.local, *.crashlytics.com
udp-replay = true

[Proxy]
🚀 Direct = direct
{$ss_group}

[Proxy Group]
🍈 Select = select{$ss_name}

🍃 Proxy = select, 🏃 Auto, 🍈 Select, 🚀 Direct

🍂 Domestic = select, 🚀 Direct, 🍃 Proxy

☁️ Others = select, 🍃 Proxy, 🚀 Direct

🏃 Auto = url-test{$ss_name}, url = http://www.gstatic.com/generate_204, interval = 1200

[Rule]
{include file='rule/PROXY.conf'}
{include file='rule/DIRECT.conf'}

GEOIP,CN,🍂 Domestic
FINAL,☁️ Others
