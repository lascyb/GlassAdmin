// 角色类型前缀
const roleTypes = ['超级管理员', '管理员', '普通用户', '访客', '测试角色', '开发人员', '产品经理', '运营人员', '客服', '财务']

/**
 * generateMockData 动态生成模拟数据
 * @param count 生成数量，默认1000
 */
const generateMockData = (count: number = 1000) => {
  const data: Array<{id: number, name: string, value: number, label: string}> = []
  
  for (let i = 1; i <= count; i++) {
    const typeIndex = (i - 1) % roleTypes.length
    const typeName = roleTypes[typeIndex] || '角色'
    const suffix = Math.floor((i - 1) / roleTypes.length)
    const name = suffix > 0 ? `${typeName}${suffix + 1}` : typeName
    
    data.push({
      id: i,
      name: name,
      value: i,
      label: `${name}(${i})`
    })
  }
  
  return data
}

// 生成所有模拟数据
const allMockData = generateMockData(1000)

/**
 * mockRoleRequestFn 角色模拟请求函数
 * @param _url 请求地址（模拟函数中未使用）
 * @param params 请求参数
 */
export const mockRoleRequestFn = async (_url: string, params: Record<string, any>) => {
  // 模拟网络延迟
  // await new Promise(resolve => setTimeout(resolve, 1000))
  
  // 如果有搜索关键词，进行过滤（支持序号、名称、标签搜索）
  let filteredData = allMockData
  const keyword = params.keyword as string | undefined
  if (keyword) {
    const keywordLower = keyword.toLowerCase().trim()
    
    // 如果关键词是纯数字，按序号搜索
    if (/^\d+$/.test(keywordLower)) {
      const num = parseInt(keywordLower)
      filteredData = allMockData.filter(item => 
        item.id === num || 
        item.value === num ||
        item.name.includes(keywordLower) ||
        item.label.includes(keywordLower)
      )
    } else {
      // 按名称和标签搜索
      filteredData = allMockData.filter(item => 
        item.name.toLowerCase().includes(keywordLower) || 
        item.label.toLowerCase().includes(keywordLower) ||
        item.id.toString().includes(keywordLower) ||
        item.value.toString().includes(keywordLower)
      )
    }
  }
  
  // 如果有初始值查询，返回匹配的选项
  if (params.init_values) {
    const initValues = Array.isArray(params.init_values) ? params.init_values : [params.init_values]
    const matchedOptions = allMockData.filter(item => initValues.includes(item.value))
    return matchedOptions.length === 1 ? matchedOptions[0] : matchedOptions
  }
  
  // 分页处理
  const page = params.page || 1
  const pageSize = params.pageSize || 10
  const start = (page - 1) * pageSize
  const end = start + pageSize
  
  return {
    list: filteredData.slice(start, end),
    total: filteredData.length
  }
}

