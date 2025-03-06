
import { Application } from 'stimulus';
import { definitionsFromContext } from 'stimulus/webpack-helpers';

// Подключение Stimulus и настройка
const application = Application.start();
const context = require.context('./controllers', true, /\.js$/);
application.load(definitionsFromContext(context));

// Если используется Bootstrap:
import 'bootstrap';