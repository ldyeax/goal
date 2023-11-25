export default {
	props: ["app", "id", "progress"],
	setup(props) {
		let app = props.app;
		let goal = app.latestGoals ?
			props.app.latestGoals[props.id] : {};
		let id = props.id;
		console.log(`cmp-goal id ${id}`);
		let progress = props.progress;
		console.log(`goal progress ${progress}`);
		return { goal, app, id, progress };
	},
	template: `
		<div class="cmp-goal">
			<h1>{{ goal?.name ?? "no goal in cmp-goal" }} {{progress*100}}%</h1>
		</div>`
};
