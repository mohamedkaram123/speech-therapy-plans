import './bootstrap';

import Alpine from 'alpinejs';
import goalsEditor from './goals-editor';

window.Alpine = Alpine;

Alpine.data('goalsEditor', goalsEditor);

Alpine.start();
