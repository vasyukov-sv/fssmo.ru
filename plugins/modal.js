import Vue from 'vue'
import Modal from '~/components/modal.vue'
import Confirm from '~/components/confirm.vue'
import * as ModalDialogs from 'vue-modal-dialogs'

Vue.use(ModalDialogs)

export default ({ app }, inject) =>
{
  	const modal = {
		show (modal, props, events)
		{
			const popup = ModalDialogs.create(Modal)

			return popup({
				component: modal,
				props: props || {},
				events: events || {},
			})
		},
		alert (props) {
			Vue.nextTick(() =>
			{
				const popup = ModalDialogs.create(Confirm)
				popup({...props})
			})
		},
		confirm (props) {
			Vue.nextTick(() =>
			{
				const popup = ModalDialogs.create(Confirm)
				popup({...props})
			})
		},
		close () {
			app.$bus.$emit('closeModals')
		},
	}

	inject('modal', modal)
}