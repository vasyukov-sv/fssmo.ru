import Vue from 'vue'
import HeaderContent from '~/components/headerContent.vue'
import EditModal from '~/components/editModal.vue'
import { morph } from '~/plugins/helpers';
import moment from 'moment';
import ru from 'moment/locale/ru'

if (!process.server)
	require('intersection-observer');

export default function ({ app, store })
{
	Vue.component('HeaderContent', HeaderContent);

	moment.locale('ru');

	Vue.filter('morph', (value, ...args) => {
		return morph(value, args);
	});

	Vue.filter('date', (value, format) => {
		return moment(value).parseZone().format(format);
	});

	Vue.filter('uppercase', (value) => {
		return value.toUpperCase();
	});

	const editorClick = (e, binding) =>
	{
		e.preventDefault();

		if (binding.arg === 'element')
		{
			if (binding.value['id'].indexOf('E') === 0)
				window.open('/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='+binding.value['iblock']+'&type=content&ID='+binding.value['id'].replace('E', '')+'&lang=ru')
		}
		else
		{
			app.$modal.show(EditModal, {
				type: binding.arg,
				value: binding.value,
				width: 1100,
				height: 700
			})
		}
	}

	Vue.directive('editor',
	{
		inserted (el, binding)
		{
			if (store.state.user && store.state.user['admin'])
			{
				el.classList.add('editor')
				el.addEventListener('click', (e) => editorClick(e, binding))
			}
		},
		unbind (el, binding) {
			el.removeEventListener('click', (e) => editorClick(e, binding))
		}
	})
}