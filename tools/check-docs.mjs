#!/usr/bin/env node
/**
 * 文档守门：每个 ga-* .vue 须有镜像 docs，并校验「已使用场景」相对链接。
 * 用法: node tools/check-docs.mjs
 */
import { readdirSync, readFileSync, statSync, existsSync } from 'node:fs'
import { dirname, join, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const errors = []

function walkVue(dir, acc = []) {
  for (const name of readdirSync(dir)) {
    const p = join(dir, name)
    const st = statSync(p)
    if (st.isDirectory()) {
      walkVue(p, acc)
    } else if (name.endsWith('.vue')) {
      acc.push(p)
    }
  }
  return acc
}

const compRoot = join(root, 'web/src/components')
const vueFiles = walkVue(compRoot)
  .map((abs) => relative(compRoot, abs).replaceAll('\\', '/'))
  .filter((rel) => rel.split('/')[0]?.startsWith('ga-'))
  .sort()

function checkUsedSection(docRel) {
  const abs = join(root, docRel)
  const text = readFileSync(abs, 'utf8')
  const re = /^## 已使用场景[^\n]*\n([\s\S]*?)(?=^## |\z)/m
  const m = text.match(re)
  if (!m) {
    errors.push(`${docRel} 缺少「已使用场景」章节`)
    return
  }
  const headingAndBody = m[0]
  const section = m[1]
  const skipRequired =
    headingAndBody.includes('暂无引用') || headingAndBody.includes('脚手架/预留')

  const docDir = dirname(abs)
  let ok = 0
  for (const href of [...section.matchAll(/\[[^\]]*\]\(([^)]+)\)/g)].map((x) => x[1].trim())) {
    if (!href || /^(https?:|mailto:|#)/.test(href)) continue
    const pathOnly = href.split('#')[0]
    if (!pathOnly) continue
    const target = resolve(docDir, pathOnly)
    if (!existsSync(target)) {
      errors.push(`${docRel} 已使用场景死链: ${href}`)
      continue
    }
    ok++
  }

  if (!skipRequired && ok === 0) {
    errors.push(
      `${docRel} 「已使用场景」无有效仓库路径（业务页应列出文件，预留组件请标注「暂无引用」或「脚手架/预留」）`,
    )
  }
}

for (const rel of vueFiles) {
  const docRel = `docs/web/src/components/${rel.replace(/\.vue$/, '.md')}`
  const docAbs = join(root, docRel)
  if (!existsSync(docAbs)) {
    errors.push(`缺少镜像文档: web/src/components/${rel} → ${docRel}`)
    continue
  }
  checkUsedSection(docRel)
}

if (errors.length) {
  for (const line of errors) console.error(line)
  console.error(`check-docs: ${errors.length} 项失败`)
  process.exit(1)
}

console.log(
  `check-docs: OK，${vueFiles.length} 个 ga-* .vue 均有镜像文档，已使用场景链接有效`,
)
