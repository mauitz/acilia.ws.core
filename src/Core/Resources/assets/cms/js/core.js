// Polyfills... because EDGE 15.
import 'core-js/features/dom-collections/for-each';

// components
import componentMarkdown from './components/ws_markdown';
import componentAssetsImage from './components/ws_assets_image';
import componentAssets from './components/ws_assets';
import componentRangeSlider from './components/ws_rangeSlider';
import componentDropdown from './components/ws_dropdown';
import componentTableCollapse from './components/ws_table_collapse';
import componentToggleChoice from './components/ws_toggle_choice';

// stimulus
import '../ts/stimulus_bootstrap.ts';

componentMarkdown();
componentAssetsImage();
componentAssets();
componentRangeSlider();
componentDropdown();
componentTableCollapse();
componentToggleChoice();
