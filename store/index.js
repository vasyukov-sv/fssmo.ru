export const state = () => ({
	page: null,
	user: null,
	menu: {
		top: [
			{url: '/about/', title: 'О нас'},
			{url: '/winners/', title: 'Победители'},
			{url: '/disciplines/', title: 'Дисциплины'},
			{url: '/news/', title: 'Новости'},
			{url: '/normatives/', title: 'Нормативы'},
			{url: '/judges/', title: 'Наши судьи'},
			{url: '/contacts/', title: 'Контакты'},
		],
		main: [
			{url: '/best-results/', title: 'Лучший результат', icon: '/images/menu-icon-1.svg'},
			{url: '/ratings/', title: 'Рейтинг стрелков', icon: '/images/menu-icon-2.svg'},
			{url: '/competitions/', title: 'Соревнования', icon: '/images/menu-icon-3.svg'},
			{url: '/results/', title: 'Результаты соревнований', icon: '/images/menu-icon-4.svg'},
			{url: '/photos/', title: 'Фото', icon: '/images/menu-icon-5.svg'},
		],
	}
});