# 数据说明：

从民政部官网 http://www.mca.gov.cn/article/sj/xzqh/1980/

202012版：http://www.mca.gov.cn/article/sj/xzqh/2020/20201201.html


德邦地址：https://vip.deppon.com/vas-cas-web/district/districtInfo



### china.object.json

- 小程序地址组件调用

### china.array.json

- 后台地址管理中调用，用于el-cascader组件

### china.code.json

- 设置快递公司价格时调用

### 　china.name.json

- 从快递公司给的价格表中提取价格用到
- 从原地址中找出地区码(用户地址确定其所地地区)

以下地区为特殊情况，要手工处理
---------------
- 4690**是海南直辖县，
- 
- 东莞市 441900 中山市 442000 嘉峪关市 620200 三沙市 460300 
- 港澳台 台湾省 710000 香港特别行政区 810000 澳门特别行政区 820000

# 修改：
修改地址后，要同时修改`common/setting/address.json`
================================
2022-04-04：
增加杭州：330113-临平区，330114-钱塘区
增加苏州：320571-苏州工业园区
