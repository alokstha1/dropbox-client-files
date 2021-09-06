import Vue from 'vue'
import App from './App.vue'
import MainFflguard from './components/MainFflguard.vue';
Vue.component( 'main-fflguard', MainFflguard );
if ( document.getElementById('dcf-app') ) {

    new Vue({
      el: '#dcf-app',
      render: h => h(App)
    })
}
