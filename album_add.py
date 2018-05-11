# -*- coding:utf-8 -*-

from urllib import request,error,parse
import requests
import random
import os
import time

#图片集发布类
class auto_add_album(object):

    __swfupload_url = ""
    __addalbum_url = ""
    __cookie = dict()

    def __init__(self, swfupload_url, addalbum_url):
        self.__swfupload_url = swfupload_url
        self.__addalbum_url = addalbum_url
        self.request_cookie()   #初始化先要对织梦系统进行一次访问，得到PHPSESSION等cookies

    def get_swfupload_url(self):
        return self.__swfupload_url

    def get_addalbum_url(self):
        return self.__addalbum_url

    def get_cookie(self):
        return self.__cookie

    def request_cookie(self):

        try:
            req = request.Request(self.__addalbum_url)

            with request.urlopen(req) as f:
                cookie = dict()
                for k,v in f.getheaders():
                    if k=='Set-Cookie':     #提取响应报文的set-cookie并转化成字典结构
                        pos = v.rindex(';')
                        tmp = v[0:pos]
                        newtmp = tmp.split(';')           
                        current = newtmp[0].split('=')
                        cookie[current[0].strip()] = current[1].strip()                  
            self.__cookie = cookie
            return True

        except request.URLError as e:
            print("URLError:"+e)
            return False

    #在发布时，要先调用uploadfile来将图片上传服务器，这里是上传一张就调用一次
    def uploadfile(self, filename, filepath):
        
        data = {'Filename':filename, 'PHPSESSID':self.__cookie['PHPSESSID'], 'Upload':'Submit Query'}

        try:
            files = {'Filedata':open(filepath,'rb')}

            requests.post(self.__swfupload_url, data=data, cookies=self.__cookie, files=files)

            return True
        except requests.HTTPError as e:
            print("HTTPError:"+e)
            return False

    #在上传完图片后，调用album_add来提交图片集的信息和确认发布图集
    def album_add(self, data):
        
        try:
            result = requests.post(self.__addalbum_url, data=data, cookies=self.__cookie)

            if result.text == "success":
                return True
            else:
                print(result.text)
                return False

        except requests.HTTPError as e:
            print("HTTPError:"+e)
            return False



#图片集发布的post数据
class postdata:

    data = {
    'cid':'0',
    'dopost':'save',
    'maxwidth':'800',
    'typeid':'2',       #栏目id--
    'ddisfirst':'1',    #第一张图片为缩略图
    'pagestyle':'2',    #表现方式
    'row':'3',
    'col':'4',
    'ddmaxwidth':'200', #缩略图最大宽度
    'pagepicnum':'12',  #每页最大图片数
    'isrm':'1',
    'delzip':'1',
    'body':'描述',      #描述内容--
    'click':str(random.randint(50,300)),      #点击数
    'notpost':'0',      #允许评论
    'sortup':'0',       #图集排序，0默认排序
    'arcrank':'0',      #阅读权限，0开放浏览
    'ishtml':'1',       #生成HTML
    'pubdate':time.strftime('%Y-%m-%d %H:%M:%S', time.localtime()),    #发布时间
    'keywords':'keyword',    #关键字--
    'imageField.x':'25', #
    'imageField.y':'12',
    'weight':str(random.randint(5,30)),      #权重
    'tags':'tag',       #标签，用,隔开--
    'title':'标题',     #标题--
    'flags[]':{"c","p","a"}, #flags: 头条[h]推荐[c]幻灯[f]特荐[a]滚动[s]加粗[b]图片[p]跳转[j]
    'channelid':'2'     #文档类型（图片集）
    }

    def get_data(self):
        return self.data

    def set_typeid(self, typeid):
        self.data['typeid'] = typeid

    def set_body(self, body):
        self.data['body'] = body

    def set_keywords(self, keywords):
        self.data['keywords'] = keywords

    def set_title(self, title):
        self.data['title'] = title

    def set_tags(self, tags):
        self.data['tags'] = tags

    def set_flags(self, flags):
        self.data['flags[]'] = flags



swfupload_url = "https://xxx/myswfupload.php"   #文件上传的接口地址
addalbum_url = "https://xxx/gs_album_add.php"   #图片集发布的接口地址

