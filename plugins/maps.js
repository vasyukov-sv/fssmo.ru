import Vue from 'vue'
import * as VueGoogleMaps from 'vue2-google-maps'

Vue.use(VueGoogleMaps, {
	load: {
		key: 'AIzaSyCunaBdILrY4wkzmacarUifOCf-7qKieaY',
		libraries: 'places,visualization'
	}
});
