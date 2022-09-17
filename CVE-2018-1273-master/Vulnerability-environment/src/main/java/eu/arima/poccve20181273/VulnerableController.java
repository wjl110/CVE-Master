package eu.arima.poccve20181273;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class VulnerableController {
	
	private static final Logger LOGGER = LoggerFactory.getLogger(VulnerableController.class);

	@PostMapping(path = "/account")
	public void doSomething(Account account) {
		LOGGER.info("Account {} received", account.getName());
	}
	
	interface Account {
		String getName();
	}
	
}
