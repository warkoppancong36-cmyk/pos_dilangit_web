/**
 * This is an advanced example for creating icon bundles for Iconify SVG Framework.
 *
 * It creates a bundle from:
 * - All SVG files in a directory.
 * - Custom JSON files.
 * - Iconify icon sets.
 * - SVG framework.
 *
 * This example uses Iconify Tools to import and clean up icons.
 * For Iconify Tools documentation visit https://docs.iconify.design/tools/tools2/
 */
import { promises as fs } from 'node:fs'
import { dirname, join } from 'node:path'

// Installation: npm install --save-dev @iconify/tools @iconify/utils @iconify/json @iconify/iconify
import { cleanupSVG, importDirectory, isEmptyColor, parseColors, runSVGO } from '@iconify/tools'
import type { IconifyJSON } from '@iconify/types'
import { getIcons, getIconsCSS, stringToIcon } from '@iconify/utils'

/**
 * Script configuration
 */
interface BundleScriptCustomSVGConfig {

  // Path to SVG files
  dir: string

  // True if icons should be treated as monotone: colors replaced with currentColor
  monotone: boolean

  // Icon set prefix
  prefix: string
}

interface BundleScriptCustomJSONConfig {

  // Path to JSON file
  filename: string

  // List of icons to import. If missing, all icons will be imported
  icons?: string[]
}

interface BundleScriptConfig {

  // Custom SVG to import and bundle
  svg?: BundleScriptCustomSVGConfig[]

  // Icons to bundled from @iconify/json packages
  icons?: string[]

  // List of JSON files to bundled
  // Entry can be a string, pointing to filename or a BundleScriptCustomJSONConfig object (see type above)
  // If entry is a string or object without 'icons' property, an entire JSON file will be bundled
  json?: (string | BundleScriptCustomJSONConfig)[]
}

const sources: BundleScriptConfig = {

  svg: [
    // {
    //   dir: 'resources/images/iconify-svg',
    //   monotone: true,
    //   prefix: 'custom',
    // },

    // {
    //   dir: 'emojis',
    //   monotone: false,
    //   prefix: 'emoji',
    // },
  ],

  icons: [
    // 'mdi:home',
    // 'mdi:account',
    // 'mdi:login',
    // 'mdi:logout',
    // 'octicon:book-24',
    // 'octicon:code-square-24',
  ],

  json: [
    // Custom JSON file
    // 'json/gg.json',

    // Iconify JSON file (@iconify/json is a package name, /json/ is directory where files are, then filename)
    require.resolve('@iconify-json/tabler/icons.json'),
    {
      filename: require.resolve('@iconify-json/mdi/icons.json'),
      icons: [
        'close-circle',
        'filter-variant',
        'language-javascript',
        'language-typescript',
        
        // Basic Actions & UI
        'plus',
        'plus-circle',
        'pencil',
        'delete',
        'close',
        'check',
        'check-circle',
        'alert-circle',
        'information',
        'content-save',
        'refresh',
        'magnify',
        'filter',
        'sort',
        'dots-vertical',
        'dots-horizontal',
        'menu',
        'home',
        'arrow-left',
        'arrow-right',
        'arrow-up',
        'arrow-down',
        'chevron-left',
        'chevron-right',
        'chevron-up',
        'chevron-down',
        
        // User Management & Authentication
        'account',
        'account-circle',
        'account-multiple',
        'account-group',
        'account-plus',
        'account-edit',
        'account-remove',
        'shield-account',
        'shield-crown',
        'login',
        'logout',
        'lock',
        'lock-open',
        'key',
        'eye',
        'eye-off',
        'shield',
        'security',
        
        // Dashboard & Analytics
        'chart-line',
        'chart-bar',
        'chart-pie',
        'analytics',
        'trending-up',
        'trending-down',
        'poll',
        'graph',
        'finance',
        'calculator',
        
        // Coffee Shop Specific
        'coffee',
        'coffee-maker',
        'coffee-outline',
        'tea',
        'tea-outline',
        'food',
        'food-croissant',
        'food-apple',
        'cake',
        'cupcake',
        'glass-cocktail',
        'bottle-soda',
        'cup',
        'glass-mug',
        'silverware-fork-knife',
        'ice-cream',
        'cookie',
        
        // Categories & Products
        'shape',
        'shape-outline',
        'tag',
        'tag-multiple',
        'label',
        'label-multiple',
        'package',
        'package-variant',
        'package-variant-closed',
        'archive',
        'folder',
        'folder-outline',
        'image',
        'image-outline',
        'camera',
        'camera-plus',
        
        // Inventory & Stock
        'warehouse',
        'store',
        'storefront',
        'briefcase',
        'box',
        'clipboard',
        'clipboard-list',
        'clipboard-check',
        'barcode',
        'barcode-scan',
        'qrcode',
        'qrcode-scan',
        'scale',
        'weight',
        
        // Sales & POS
        'cash-register',
        'cash',
        'cash-plus',
        'cash-minus',
        'cash-multiple',
        'currency-usd',
        'sale',
        'receipt',
        'shopping',
        'shopping-cart',
        'cart',
        'cart-plus',
        'cart-minus',
        'credit-card',
        'wallet',
        'payment',
        'point-of-sale',
        
        // Orders & Transactions
        'clipboard-text',
        'receipt-text',
        'file-document',
        'file-document-outline',
        'note',
        'note-text',
        'order-bool-descending',
        'basket',
        'bag-personal',
        'package-down',
        'truck-delivery',
        'clock',
        'clock-outline',
        'timer',
        'history',
        
        // Customer & CRM
        'account-heart',
        'account-star',
        'star',
        'star-outline',
        'heart',
        'heart-outline',
        'phone',
        'email',
        'map-marker',
        'card-account-details',
        'contacts',
        
        // Tables & Seating
        'table-chair',
        'chair-rolling',
        'sofa',
        'desk',
        'home-variant',
        'floor-plan',
        'grid',
        'view-grid',
        'view-list',
        
        // Kitchen & Staff
        'chef-hat',
        'fire',
        'stove',
        'pot',
        'pot-steam',
        'fridge',
        'microwave',
        'toaster',
        'blender',
        'scale-bathroom',
        'thermometer',
        'timer-sand',
        'bell',
        'bell-ring',
        
        // Suppliers & Purchasing
        'truck',
        'factory',
        'handshake',
        'briefcase-variant',
        'cart-arrow-down',
        'package-up',
        'import',
        'export',
        'swap-horizontal',
        
        // Reports & Documents
        'file-chart',
        'chart-box',
        'printer',
        'file-pdf-box',
        'file-excel-box',
        'download',
        'upload',
        'cloud-download',
        'cloud-upload',
        'backup-restore',
        
        // Settings & Configuration
        'cog',
        'settings',
        'tune',
        'wrench',
        'tools',
        'palette',
        'theme-light-dark',
        'brightness-6',
        'monitor',
        'cellphone',
        'tablet',
        
        // Date & Time
        'calendar',
        'calendar-today',
        'calendar-month',
        'calendar-week',
        'calendar-clock',
        'clock-time-four',
        'schedule',
        'alarm',
        
        // Status & States
        'pause',
        'play',
        'stop',
        'record',
        'power',
        'power-on',
        'power-off',
        'wifi',
        'wifi-off',
        'signal',
        'battery',
        'battery-charging',
        
        // Notifications & Communication
        'bell-outline',
        'message',
        'message-text',
        'chat',
        'forum',
        'send',
        'share',
        'link',
        'phone-classic',
        'whatsapp',
        
        // Taxes & Financial
        'percent',
        'percentage',
        'calculator-variant',
        'bank',
        'piggy-bank',
        'coin',
        'currency-rupiah',
        'currency-eur',
        'currency-gbp',
        'trending-neutral',
        
        // Loyalty & Promotions
        'gift',
        'gift-outline',
        'ticket',
        'ticket-percent',
        'medal',
        'trophy',
        'crown',
        'diamond',
        'card-membership',
        'voucher',
        
        // Time Management & Shifts
        'clock-in',
        'clock-out',
        'timetable',
        'calendar-account',
        'account-clock',
        'briefcase-clock',
        'worker',
        'human-greeting',
        
        // System & Technical
        'database',
        'server',
        'lan',
        'router-wireless',
        'sync',
        'reload',
        'cached',
        'bug',
        'code-tags',
        'console',
        'terminal',
        
        // Navigation & Layout
        'view-dashboard',
        'view-module',
        'apps',
        'widgets',
        'fullscreen',
        'fullscreen-exit',
        'fit-to-page',
        'resize',
        'drag',
        'cursor-move',
        
        // Weather & Environment  
        'weather-sunny',
        'weather-cloudy',
        'weather-rainy',
        'thermometer-lines',
        'air-conditioner',
        'fan',
        'lightbulb',
        'lightbulb-outline',
        
        // Additional Utilities
        'help',
        'help-circle',
        'information-outline',
        'frequently-asked-questions',
        'book',
        'book-open',
        'school',
        'graduation-cap',
        'certificate',
        'badge',
        'medal-outline',
        'ribbon',
        
        // Social & Reviews
        'thumb-up',
        'thumb-down',
        'emoticon-happy',
        'emoticon-sad',
        'emoticon-neutral',
        'comment',
        'comment-multiple',
        'forum-outline',
        'facebook',
        'instagram',
        'twitter',
        
        // Emergency & Safety
        'alert',
        'alert-outline',
        'shield-alert',
        'fire-extinguisher',
        'medical-bag',
        'hospital',
        'phone-alert',
        'emergency',
        
        // Misc Business
        'office-building',
        'domain',
        'city',
        'map',
        'compass',
        'flag',
        'bookmark',
        'bookmark-outline',
        'pin',
        'pin-outline',
      ],
    },
    {
      filename: require.resolve('@iconify-json/fa/icons.json'),
      icons: [
        'circle',
      ],
    },

    // Custom file with only few icons
    // {
    //   filename: require.resolve('@iconify-json/line-md/icons.json'),
    //   icons: [
    //     'home-twotone-alt',
    //     'github',
    //     'document-list',
    //     'document-code',
    //     'image-twotone',
    //   ],
    // },
  ],
}

// File to save bundle to
const target = join(__dirname, 'icons.css')

/**
 * Do stuff!
 */

;(async function () {
  // Create directory for output if missing
  const dir = dirname(target)
  try {
    await fs.mkdir(dir, {
      recursive: true,
    })
  }
  catch (err) {
    //
  }

  const allIcons: IconifyJSON[] = []

  /**
   * Convert sources.icons to sources.json
   */
  if (sources.icons) {
    const sourcesJSON = sources.json ? sources.json : (sources.json = [])

    // Sort icons by prefix
    const organizedList = organizeIconsList(sources.icons)

    for (const prefix in organizedList) {
      const filename = require.resolve(`@iconify/json/json/${prefix}.json`)

      sourcesJSON.push({
        filename,
        icons: organizedList[prefix],
      })
    }
  }

  /**
   * Bundle JSON files and collect icons
   */
  if (sources.json) {
    for (let i = 0; i < sources.json.length; i++) {
      const item = sources.json[i]

      // Load icon set
      const filename = typeof item === 'string' ? item : item.filename
      const content = JSON.parse(await fs.readFile(filename, 'utf8')) as IconifyJSON

      for (const key in content) {
        if (key === 'prefix' && content.prefix === 'tabler') {
          for (const k in content.icons)
            content.icons[k].body = content.icons[k].body.replace(/stroke-width="2"/g, 'stroke-width="1.5"')
        }
      }

      // Filter icons
      if (typeof item !== 'string' && item.icons?.length) {
        const filteredContent = getIcons(content, item.icons)

        if (!filteredContent)
          throw new Error(`Cannot find required icons in ${filename}`)

        // Collect filtered icons
        allIcons.push(filteredContent)
      }
      else {
        // Collect all icons from the JSON file
        allIcons.push(content)
      }
    }
  }

  /**
   * Bundle custom SVG icons and collect icons
   */
  if (sources.svg) {
    for (let i = 0; i < sources.svg.length; i++) {
      const source = sources.svg[i]

      // Import icons
      const iconSet = await importDirectory(source.dir, {
        prefix: source.prefix,
      })

      // Validate, clean up, fix palette, etc.
      await iconSet.forEach(async (name, type) => {
        if (type !== 'icon')
          return

        // Get SVG instance for parsing
        const svg = iconSet.toSVG(name)

        if (!svg) {
          // Invalid icon
          iconSet.remove(name)

          return
        }

        // Clean up and optimise icons
        try {
          // Clean up icon code
          await cleanupSVG(svg)

          if (source.monotone) {
            // Replace color with currentColor, add if missing
            // If icon is not monotone, remove this code
            await parseColors(svg, {
              defaultColor: 'currentColor',
              callback: (attr, colorStr, color) => {
                return !color || isEmptyColor(color) ? colorStr : 'currentColor'
              },
            })
          }

          // Optimise
          await runSVGO(svg)
        }
        catch (err) {
          // Invalid icon
          console.error(`Error parsing ${name} from ${source.dir}:`, err)
          iconSet.remove(name)

          return
        }

        // Update icon from SVG instance
        iconSet.fromSVG(name, svg)
      })

      // Collect the SVG icon
      allIcons.push(iconSet.export())
    }
  }

  // Generate CSS from collected icons
  const cssContent = allIcons
    .map(iconSet => getIconsCSS(
      iconSet,
      Object.keys(iconSet.icons),
      {
        iconSelector: '.{prefix}-{name}',
        mode: 'mask',
      },
    ))
    .join('\n')

  // Save the CSS to a file
  await fs.writeFile(target, cssContent, 'utf8')

  console.log(`Saved CSS to ${target}!`)
})().catch(err => {
  console.error(err)
})

/**
 * Sort icon names by prefix
 */
function organizeIconsList(icons: string[]): Record<string, string[]> {
  const sorted: Record<string, string[]> = Object.create(null)

  icons.forEach(icon => {
    const item = stringToIcon(icon)

    if (!item)
      return

    const prefix = item.prefix
    const prefixList = sorted[prefix] ? sorted[prefix] : (sorted[prefix] = [])

    const name = item.name

    if (!prefixList.includes(name))
      prefixList.push(name)
  })

  return sorted
}
