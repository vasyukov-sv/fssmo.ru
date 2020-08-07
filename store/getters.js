import { convertMeta } from '~/plugins/helpers'

export default {
	isAuthorized: state => {
		return state.user !== null
	},
	metaTags: state => {
		return convertMeta(state.page)
	},
	getTopMenu: state => {
		return state.menu.top
	},
	getMainMenu: state => {
		return state.menu.main
	}
};