# -*- coding:utf-8 -*-
import subprocess
import time

#定时发布器
while True:
    starttime = time.strftime("%Y-%m-%d %H:%M:%S")
    r = subprocess.Popen(['python', 'album_add.py'], stdout=subprocess.PIPE)
    tmpstr = r.stdout.read()
    tmpstr = tmpstr.decode("utf-8")
    nowtime = time.strftime("%Y-%m-%d %H:%M:%S")
    log = starttime + ":\n" + tmpstr + nowtime + ": All update success!\n"
    f = open("E:/xxx/update.log", 'a')
    f.write(log)
    f.close()
    print("finish!")
    time.sleep(86400)