export default {
	props: ["app", "id"],
	setup(props) {
		let app = props.app;
		let goal = app.latestGoals ?
			props.app.latestGoals[props.id] : {};
		let id = props.id;
		return { goal, app, id };
	},
	template: `
		<div class="cmp-goal">
			<h1>{{ goal?.name ?? "no goal in cmp-goal" }}</h1>
		</div>`
};
