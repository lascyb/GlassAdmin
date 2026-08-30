/**
 * 主题色纯函数自检：node --experimental-strip-types --no-warnings src/theme/color.test.ts
 */
import {
    THEME_COLOR_DEFAULT,
    ON_PRIMARY_LIGHT,
    ON_PRIMARY_DARK,
    THEME_SETTINGS_VERSION,
    normalizeThemeColor,
    onPrimaryOf,
    parseHexColor,
    isCurrentThemeVersion,
} from './color.ts'

const assert = (ok: boolean, label: string) => {
    if (!ok) {
        console.error('FAIL', label)
        process.exit(1)
    }
    console.log('OK', label)
}

assert(normalizeThemeColor('#409EFF') === THEME_COLOR_DEFAULT, 'normalize upper hex')
assert(normalizeThemeColor('#48f') === '#4488ff', 'normalize #rgb')
assert(normalizeThemeColor('#409effcc') === THEME_COLOR_DEFAULT, 'strip #rrggbbaa alpha')
assert(normalizeThemeColor('blue') === THEME_COLOR_DEFAULT, 'reject named color')
assert(normalizeThemeColor('') === THEME_COLOR_DEFAULT, 'reject empty')
assert(normalizeThemeColor(null) === THEME_COLOR_DEFAULT, 'reject null')
assert(parseHexColor('#409eff') !== null, 'parse valid')
assert(parseHexColor('rgb(64,158,255)') === null, 'reject rgb()')
assert(onPrimaryOf('#409eff') === ON_PRIMARY_LIGHT, 'default blue uses white text')
assert(onPrimaryOf('#facc15') === ON_PRIMARY_DARK, 'bright yellow uses dark text')
assert(onPrimaryOf('#111111') === ON_PRIMARY_LIGHT, 'near black uses white text')
assert(isCurrentThemeVersion(THEME_SETTINGS_VERSION), 'current version matches')
assert(!isCurrentThemeVersion(0), 'old version rejected')
assert(!isCurrentThemeVersion(undefined), 'missing version rejected')

console.log('ALL OK')
